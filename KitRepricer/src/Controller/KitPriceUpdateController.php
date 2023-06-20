<?php

declare(strict_types=1);

namespace KitAutoPriceUpdate\Controller;

use DateTime;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use KitAutoPriceUpdate\Helper\Utility;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use function array_unique;

/**
 * @RouteScope(scopes={"api"})
 */
class KitPriceUpdateController extends AbstractController
{
    private function getConnection()
    {
        return $this->container->get(Connection::class);
    }

    /**
     * @Route("/api/_action/kit/get-base", name="api.action.kit.get-base", methods={"GET"})
     */
    public function getBaseRule(Request $request, Context $context): JsonResponse
    {
        $sql = 'SELECT *, LOWER(HEX(`id`)) AS `id` FROM kit_priceupdate_base_rules WHERE `type` = ?';
        $data = $this->getConnection()->fetchAssociative($sql, [$request->get('type')]);

        $data = Utility::parseRulesData($data);

        return new JsonResponse(['data' => $data]);
    }

    /**
     * @Route("/api/_action/kit/save-base", name="api.action.kit.save-base", methods={"POST"})
     */
    public function saveBaseRule(Request $request, Context $context): JsonResponse
    {
        $params = $request->request->all();
        $type = $params['type'];

        $columns[] = 'id';
        $placeholder[] = '?';
        $values[] = Uuid::fromHexToBytes(md5($type));

        foreach ($params as $key => $value) {
            $columns[] = $key;
            $placeholder[] = '?';
            if (is_array($value)) {
                $value = implode(',', array_unique($value));
            }
            $values[] = $value;
        }

        $columns[] = 'created_at';
        $placeholder[] = '?';
        $values[] = (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $sql = sprintf(
            'REPLACE INTO kit_priceupdate_base_rules (%s) VALUES (%s)',
            implode(',', $columns),
            implode(',', $placeholder)
        );

        $this->getConnection()->executeStatement($sql, $values);

        return new JsonResponse(['success' => true, 'message' => sprintf('Rule "%s" saved successfully', $type)]);
    }

    /**
     * @Route("/api/_action/kit/logs", name="api.action.kit.logs", methods={"GET"})
     */
    public function getLogsList(Request $request, Context $context): JsonResponse
    {
        $articlenumber = $request->get('term', false);
        if ($articlenumber) {
            $sql =
                'SELECT * FROM kit_priceupdate_logs WHERE `artId` LIKE ? ORDER BY created_at DESC';
            $data = $this->getConnection()->fetchAllAssociative($sql, ['%' . $articlenumber . '%']);
        } else {
            $sql = 'SELECT * FROM kit_priceupdate_logs ORDER BY created_at DESC LIMIT 200';
            $data = $this->getConnection()->fetchAllAssociative($sql);
        }

        $data = Utility::convertDateInArrayToDE($data);

        return new JsonResponse(['data' => $data]);
    }

    /**
     * @Route("/api/_action/kit/list", name="api.action.kit.list", methods={"GET"})
     */
    public function getExceptionList(Request $request, Context $context): JsonResponse
    {
        $sql = 'SELECT *, LOWER(HEX(`id`)) AS `id` FROM kit_priceupdate_exception_rules ORDER BY priority DESC, type';
        $data = $this->getConnection()->fetchAllAssociative($sql);

        return new JsonResponse(['data' => $data]);
    }

    /**
     * @Route("/api/_action/kit/get-rule", name="api.action.kit.get-rule", methods={"GET"})
     */
    public function getExceptionRule(Request $request, Context $context): JsonResponse
    {
        $sql = 'SELECT *, LOWER(HEX(`id`)) AS `id` FROM kit_priceupdate_exception_rules WHERE `id` = UNHEX(?)';
        $data = $this->getConnection()->fetchAssociative($sql, [$request->get('ruleId')]);

        $data = Utility::parseRulesData($data);

        return new JsonResponse(['data' => $data]);
    }

    /**
     * @Route("/api/_action/kit/save-rule", name="api.action.kit.save-rule", methods={"POST"})
     */
    public function saveExceptionRule(Request $request, Context $context): JsonResponse
    {
        $params = $request->request->all();
        if (isset($params['id'])) {
            return $this->updateRule($params);
        }

        $id = Uuid::randomHex();
        $columns[] = 'id';
        $placeholder[] = '?';
        $values[] = $id;

        foreach ($params as $key => $value) {
            $columns[] = $key;
            $placeholder[] = '?';
            if (is_array($value)) {
                $value = implode(',', array_unique($value));
            }
            $values[] = $value;
        }

        $columns[] = 'created_at';
        $placeholder[] = '?';
        $values[] = (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $sql = sprintf(
            'INSERT INTO kit_priceupdate_exception_rules (%s) VALUES (%s)',
            implode(',', $columns),
            implode(',', $placeholder)
        );

        $this->getConnection()->executeStatement($sql, $values);

        return new JsonResponse(
            [
                'success' => true,
                'message' => sprintf('Rule "%s" saved successfully', $params['name'])
            ]
        );
    }

    /**
     * @Route("/api/_action/kit/delete-rule", name="api.action.kit.delete-rule", methods={"POST"})
     */
    public function deleteExceptionRule(Request $request, Context $context): JsonResponse
    {
        $params = $request->request->all();
        if (!isset($params['id'])) {
            throw new InvalidArgumentException('Provided rule does not exist');
        }

        $sql = 'DELETE FROM kit_priceupdate_exception_rules WHERE (id = UNHEX(?))';

        $this->getConnection()->executeStatement($sql, [$params['id']]);

        return new JsonResponse(['success' => true, 'message' => 'Rule deleted successfully']);
    }

    private function updateRule(array $params): ?JsonResponse
    {
        $values = [];
        $placeholder = [];
        $id = $params['id'];
        unset($params['id']);

        foreach ($params as $key => $value) {
            $placeholder[] = sprintf('%s = ?', $key);
            if (is_array($value)) {
                $value = implode(',', array_filter($value));
            }
            $values[] = $value;
        }

        $sql = sprintf(
            'UPDATE kit_priceupdate_exception_rules SET %s WHERE id = UNHEX(%s)',
            implode(',', $placeholder),
            $id
        );

        $this->getConnection()->executeStatement($sql, $values);

        return new JsonResponse(['success' => true, 'message' => 'Rule updated successfully']);
    }
}
