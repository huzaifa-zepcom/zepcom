<?php

declare(strict_types=1);

namespace NetzBirthdayReminder\Command;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Content\Mail\Service\MailFactory;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Validator\Constraints\NotBlank;

class BirthdayReminderCommand extends Command
{
    protected static $defaultName = 'netz:preBirthdayReminder';

    protected Connection $connection;

    protected EntityRepository $customerRepository;

    protected ContainerInterface $container;

    protected StringTemplateRenderer $templateRenderer;

    protected MailFactory $mailFactory;

    protected AbstractMailSender $mailSender;

    protected DataValidator $dataValidator;

    protected SalesChannelDefinition $salesChannelDefinition;

    protected UrlGeneratorInterface $urlGenerator;

    protected SystemConfigService $configService;

    public function __construct(
        Connection $connection,
        EntityRepository $customerRepository,
        StringTemplateRenderer $templateRenderer,
        MailFactory $mailFactory,
        AbstractMailSender $mailSender,
        DataValidator $dataValidator,
        SalesChannelDefinition $salesChannelDefinition,
        UrlGeneratorInterface $urlGenerator,
        SystemConfigService $configService,
        ContainerInterface $container
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->customerRepository = $customerRepository;
        $this->container = $container;
        $this->templateRenderer = $templateRenderer;
        $this->mailFactory = $mailFactory;
        $this->mailSender = $mailSender;
        $this->dataValidator = $dataValidator;
        $this->salesChannelDefinition = $salesChannelDefinition;
        $this->urlGenerator = $urlGenerator;
        $this->configService = $configService;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $context = new Context(new SystemSource());
        $ts = strtotime('+14 days');
        $date = date('m-d', $ts);
        $io->title('Looking for birthdays on: ' . date('d.M.Y', strtotime(date('Y') . '-' . $date)));

        $sql = <<<SQL
SELECT LOWER(HEX(c.id)) AS `id` FROM `order` o
INNER JOIN order_customer oc ON oc.order_id = o.id AND oc.order_version_id = o.version_id
INNER JOIN customer c on c.id = oc.customer_id
WHERE `order_date_time` >= DATE_SUB(now(), INTERVAL 12 MONTH) AND c.birthday LIKE "%-' . $date . '"
GROUP BY oc.customer_id
SQL;

        $ids = $this->connection->fetchAllAssociative($sql);
        if ($ids) {
            $users = $this->getCustomersByIds($ids, $context);
            $io->note('Found ' . $users->count() . ' customers');
            $data = [];
            /** @var CustomerEntity $user */
            foreach ($users as $user) {
                $shipping = $user->getDefaultShippingAddress();
                $data[] = [
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'street' => $shipping->getStreet(),
                    'zipcode' => $shipping->getZipcode(),
                    'city' => $shipping->getCity(),
                    'netz_dog_name' => $user->getCustomFields()['netz_dog_name'],
                    'birthday' => $user->getBirthday(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getDefaultBillingAddress()->getPhoneNumber()
                ];
            }

            $template = $this->getMailTemplate($context);
            $this->sendMail($template, ['users' => $data], $context);

            $io->success('Processed ' . count($data) . ' records');
        } else {
            $io->warning('No records found');
        }

        return 0;
    }

    private function getCustomersByIds(array $ids, Context $context)
    {
        $criteria = new Criteria($ids);
        $criteria->addAssociation('defaultShippingAddress');
        $criteria->addAssociation('defaultBillingAddress');

        return $this->customerRepository->search($criteria, $context)->getEntities();
    }

    public function getMailTemplate($context): MailTemplateEntity
    {
        $technicalName = 'netz_birthday_reminder';
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->setLimit(1);

        return $this->container->get('mail_template.repository')->search($criteria, $context)->first();
    }

    public function sendMail(
        MailTemplateEntity $mailTemplate,
        array $emailData,
        $context
    ): void {
        $data = new ParameterBag();
        $data->set('senderName', $mailTemplate->getSenderName());
        $data->set('contentHtml', $mailTemplate->getContentHtml());
        $data->set('contentPlain', $mailTemplate->getContentPlain());
        $data->set('subject', $mailTemplate->getSubject());

        $salesChannelCriteria = new Criteria();
        $salesChannelCriteria->addAssociation('mailHeaderFooter');
        $salesChannelCriteria->getAssociation('domains')->addFilter(new EqualsFilter('languageId', Defaults::LANGUAGE_SYSTEM));
        $salesChannel = $this->container->get('sales_channel.repository')->search($salesChannelCriteria, $context)->first();

        $data->set('salesChannelId', $salesChannel->getId());
        $emailData['salesChannel'] = $salesChannel;

        $recipients = [
            'ginanagel@bubeck-petfood.de' => 'ginanagel@bubeck-petfood.de',
            'kainagel@bubeck-petfood.de' => 'kainagel@bubeck-petfood.de',
        ];

        $data->set('recipients', $recipients);
        $message = $this->populateTemplate($data->all(), $context, $emailData);

        if ($message) {
            $email = $this->mailFactory->create(
                $message['subject'],
                [
                    $this->getSender($emailData, $salesChannel->getId()) => $mailTemplate->getSenderName()
                ],
                $recipients,
                $message['content'],
                [],
                ['recipientsBcc' => 'wershofen@netzturm.com']
            );

            $this->mailSender->send($email);
        }
    }

    private function populateTemplate(array $data, Context $context, array $templateData = []): ?array
    {
        $definition = $this->getValidationDefinition($context);
        $this->dataValidator->validate($data, $definition);

        $salesChannel = $templateData['salesChannel'];
        $contents = $this->buildContents($data, $salesChannel);
        $this->templateRenderer->initialize();
        $template = $data['subject'];

        try {
            $data['subject'] = $this->templateRenderer->render($template, $templateData, $context);
            $template = $data['senderName'];
            $data['senderName'] = $this->templateRenderer->render($template, $templateData, $context);

            foreach ($contents as $index => $template) {
                $contents[$index] = $this->templateRenderer->render($template, $templateData, $context);
            }
        } catch (Exception $e) {
            $contents['text/html'] = "Could not render Mail-Template with error message:\n"
                . $e->getMessage() . "\n"
                . 'Error Code:' . $e->getCode() . "\n"
                . 'Template source:'
                . $template . "\n";
        }

        $data['content'] = $contents;

        return $data;
    }

    private function buildContents(array $data, ?SalesChannelEntity $salesChannel): array
    {
        if ($salesChannel && $mailHeaderFooter = $salesChannel->getMailHeaderFooter()) {
            return [
                'text/plain' => $mailHeaderFooter->getHeaderPlain() . $data['contentPlain'] .
                    $mailHeaderFooter->getFooterPlain(),
                'text/html' => $mailHeaderFooter->getHeaderHtml() . $data['contentHtml'] .
                    $mailHeaderFooter->getFooterHtml(),
            ];
        }

        return [
            'text/html' => $data['contentHtml'],
            'text/plain' => $data['contentPlain'],
        ];
    }

    private function getValidationDefinition(Context $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('mail_service.send');

        $definition->add('recipients', new NotBlank());
        $definition->add(
            'salesChannelId',
            new EntityExists(
                [
                    'entity' => 'sales_channel',
                    'context' => $context
                ]
            )
        );
        $definition->add('contentHtml', new NotBlank());
        $definition->add('contentPlain', new NotBlank());
        $definition->add('subject', new NotBlank());
        $definition->add('senderName', new NotBlank());

        return $definition;
    }

    private function getSender($data, ?string $salesChannelId): ?string
    {
        $senderEmail = $data['senderEmail'] ?? null;

        if ($senderEmail === null || trim($senderEmail) === '') {
            $senderEmail = $this->configService->get('core.basicInformation.email', $salesChannelId);
        }

        if ($senderEmail === null || trim($senderEmail) === '') {
            $senderEmail = $this->configService->get('core.mailerSettings.senderAddress', $salesChannelId);
        }

        if ($senderEmail === null || trim($senderEmail) === '') {
            return null;
        }

        return $senderEmail;
    }
}
