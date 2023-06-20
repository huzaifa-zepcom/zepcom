<?php

namespace KitAutoPriceUpdate\Content\Repricer;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class RepricerEntity extends Entity
{
    use EntityIdTrait;

    /** @var string */
    protected $productId;

    /** @var ProductEntity */
    protected $product;

    /** @var int */
    protected $geizhalsID;

    /** @var string|null */
    protected $geizhalsArtikelname;

    /** @var string */
    protected $meinPreis;

    /** @var string|null */
    protected $price1;

    /** @var string|null */
    protected $anbieter1;

    /** @var string|null */
    protected $lz1;

    /** @var string|null */
    protected $price2;

    /** @var string|null */
    protected $anbieter2;

    /** @var string|null */
    protected $lz2;

    /** @var string|null */
    protected $price3;

    /** @var string|null */
    protected $anbieter3;

    /** @var string|null */
    protected $lz3;

    /** @var string|null */
    protected $price4;

    /** @var string|null */
    protected $anbieter4;

    /** @var string|null */
    protected $lz4;

    /** @var string|null */
    protected $price5;

    /** @var string|null */
    protected $anbieter5;

    /** @var string|null */
    protected $lz5;

    /** @var string|null */
    protected $price6;

    /** @var string|null */
    protected $anbieter6;

    /** @var string|null */
    protected $lz6;

    /** @var string|null */
    protected $price7;

    /** @var string|null */
    protected $anbieter7;

    /** @var string|null */
    protected $lz7;

    /** @var string|null */
    protected $price8;

    /** @var string|null */
    protected $anbieter8;

    /** @var string|null */
    protected $lz8;

    /** @var string|null */
    protected $price9;

    /** @var string|null */
    protected $anbieter9;

    /** @var string|null */
    protected $lz9;

    /** @var string|null */
    protected $price10;

    /** @var string|null */
    protected $anbieter10;

    /** @var string|null */
    protected $lz10;

    /** @var string */
    protected $meineArtikelnummer;

    /** @var string|null */
    protected $geizhalsArtikelURL;

    /**
     * @return string
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     */
    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @return ProductEntity
     */
    public function getProduct(): ProductEntity
    {
        return $this->product;
    }

    /**
     * @param ProductEntity $product
     */
    public function setProduct(ProductEntity $product): void
    {
        $this->product = $product;
    }

    /**
     * @return int
     */
    public function getGeizhalsID(): int
    {
        return $this->geizhalsID;
    }

    /**
     * @param int $geizhalsID
     */
    public function setGeizhalsID(int $geizhalsID): void
    {
        $this->geizhalsID = $geizhalsID;
    }

    /**
     * @return string|null
     */
    public function getGeizhalsArtikelname(): ?string
    {
        return $this->geizhalsArtikelname;
    }

    /**
     * @param string|null $geizhalsArtikelname
     */
    public function setGeizhalsArtikelname(?string $geizhalsArtikelname): void
    {
        $this->geizhalsArtikelname = $geizhalsArtikelname;
    }

    /**
     * @return string
     */
    public function getMeinPreis(): string
    {
        return $this->meinPreis;
    }

    /**
     * @param string $meinPreis
     */
    public function setMeinPreis(string $meinPreis): void
    {
        $this->meinPreis = $meinPreis;
    }

    /**
     * @return string|null
     */
    public function getPrice1(): ?string
    {
        return $this->price1;
    }

    /**
     * @param string|null $price1
     */
    public function setPrice1(?string $price1): void
    {
        $this->price1 = $price1;
    }

    /**
     * @return string|null
     */
    public function getAnbieter1(): ?string
    {
        return $this->anbieter1;
    }

    /**
     * @param string|null $anbieter1
     */
    public function setAnbieter1(?string $anbieter1): void
    {
        $this->anbieter1 = $anbieter1;
    }

    /**
     * @return string|null
     */
    public function getLz1(): ?string
    {
        return $this->lz1;
    }

    /**
     * @param string|null $lz1
     */
    public function setLz1(?string $lz1): void
    {
        $this->lz1 = $lz1;
    }

    /**
     * @return string|null
     */
    public function getPrice2(): ?string
    {
        return $this->price2;
    }

    /**
     * @param string|null $price2
     */
    public function setPrice2(?string $price2): void
    {
        $this->price2 = $price2;
    }

    /**
     * @return string|null
     */
    public function getAnbieter2(): ?string
    {
        return $this->anbieter2;
    }

    /**
     * @param string|null $anbieter2
     */
    public function setAnbieter2(?string $anbieter2): void
    {
        $this->anbieter2 = $anbieter2;
    }

    /**
     * @return string|null
     */
    public function getLz2(): ?string
    {
        return $this->lz2;
    }

    /**
     * @param string|null $lz2
     */
    public function setLz2(?string $lz2): void
    {
        $this->lz2 = $lz2;
    }

    /**
     * @return string|null
     */
    public function getPrice3(): ?string
    {
        return $this->price3;
    }

    /**
     * @param string|null $price3
     */
    public function setPrice3(?string $price3): void
    {
        $this->price3 = $price3;
    }

    /**
     * @return string|null
     */
    public function getAnbieter3(): ?string
    {
        return $this->anbieter3;
    }

    /**
     * @param string|null $anbieter3
     */
    public function setAnbieter3(?string $anbieter3): void
    {
        $this->anbieter3 = $anbieter3;
    }

    /**
     * @return string|null
     */
    public function getLz3(): ?string
    {
        return $this->lz3;
    }

    /**
     * @param string|null $lz3
     */
    public function setLz3(?string $lz3): void
    {
        $this->lz3 = $lz3;
    }

    /**
     * @return string|null
     */
    public function getPrice4(): ?string
    {
        return $this->price4;
    }

    /**
     * @param string|null $price4
     */
    public function setPrice4(?string $price4): void
    {
        $this->price4 = $price4;
    }

    /**
     * @return string|null
     */
    public function getAnbieter4(): ?string
    {
        return $this->anbieter4;
    }

    /**
     * @param string|null $anbieter4
     */
    public function setAnbieter4(?string $anbieter4): void
    {
        $this->anbieter4 = $anbieter4;
    }

    /**
     * @return string|null
     */
    public function getLz4(): ?string
    {
        return $this->lz4;
    }

    /**
     * @param string|null $lz4
     */
    public function setLz4(?string $lz4): void
    {
        $this->lz4 = $lz4;
    }

    /**
     * @return string|null
     */
    public function getPrice5(): ?string
    {
        return $this->price5;
    }

    /**
     * @param string|null $price5
     */
    public function setPrice5(?string $price5): void
    {
        $this->price5 = $price5;
    }

    /**
     * @return string|null
     */
    public function getAnbieter5(): ?string
    {
        return $this->anbieter5;
    }

    /**
     * @param string|null $anbieter5
     */
    public function setAnbieter5(?string $anbieter5): void
    {
        $this->anbieter5 = $anbieter5;
    }

    /**
     * @return string|null
     */
    public function getLz5(): ?string
    {
        return $this->lz5;
    }

    /**
     * @param string|null $lz5
     */
    public function setLz5(?string $lz5): void
    {
        $this->lz5 = $lz5;
    }

    /**
     * @return string|null
     */
    public function getPrice6(): ?string
    {
        return $this->price6;
    }

    /**
     * @param string|null $price6
     */
    public function setPrice6(?string $price6): void
    {
        $this->price6 = $price6;
    }

    /**
     * @return string|null
     */
    public function getAnbieter6(): ?string
    {
        return $this->anbieter6;
    }

    /**
     * @param string|null $anbieter6
     */
    public function setAnbieter6(?string $anbieter6): void
    {
        $this->anbieter6 = $anbieter6;
    }

    /**
     * @return string|null
     */
    public function getLz6(): ?string
    {
        return $this->lz6;
    }

    /**
     * @param string|null $lz6
     */
    public function setLz6(?string $lz6): void
    {
        $this->lz6 = $lz6;
    }

    /**
     * @return string|null
     */
    public function getPrice7(): ?string
    {
        return $this->price7;
    }

    /**
     * @param string|null $price7
     */
    public function setPrice7(?string $price7): void
    {
        $this->price7 = $price7;
    }

    /**
     * @return string|null
     */
    public function getAnbieter7(): ?string
    {
        return $this->anbieter7;
    }

    /**
     * @param string|null $anbieter7
     */
    public function setAnbieter7(?string $anbieter7): void
    {
        $this->anbieter7 = $anbieter7;
    }

    /**
     * @return string|null
     */
    public function getLz7(): ?string
    {
        return $this->lz7;
    }

    /**
     * @param string|null $lz7
     */
    public function setLz7(?string $lz7): void
    {
        $this->lz7 = $lz7;
    }

    /**
     * @return string|null
     */
    public function getPrice8(): ?string
    {
        return $this->price8;
    }

    /**
     * @param string|null $price8
     */
    public function setPrice8(?string $price8): void
    {
        $this->price8 = $price8;
    }

    /**
     * @return string|null
     */
    public function getAnbieter8(): ?string
    {
        return $this->anbieter8;
    }

    /**
     * @param string|null $anbieter8
     */
    public function setAnbieter8(?string $anbieter8): void
    {
        $this->anbieter8 = $anbieter8;
    }

    /**
     * @return string|null
     */
    public function getLz8(): ?string
    {
        return $this->lz8;
    }

    /**
     * @param string|null $lz8
     */
    public function setLz8(?string $lz8): void
    {
        $this->lz8 = $lz8;
    }

    /**
     * @return string|null
     */
    public function getPrice9(): ?string
    {
        return $this->price9;
    }

    /**
     * @param string|null $price9
     */
    public function setPrice9(?string $price9): void
    {
        $this->price9 = $price9;
    }

    /**
     * @return string|null
     */
    public function getAnbieter9(): ?string
    {
        return $this->anbieter9;
    }

    /**
     * @param string|null $anbieter9
     */
    public function setAnbieter9(?string $anbieter9): void
    {
        $this->anbieter9 = $anbieter9;
    }

    /**
     * @return string|null
     */
    public function getLz9(): ?string
    {
        return $this->lz9;
    }

    /**
     * @param string|null $lz9
     */
    public function setLz9(?string $lz9): void
    {
        $this->lz9 = $lz9;
    }

    /**
     * @return string|null
     */
    public function getPrice10(): ?string
    {
        return $this->price10;
    }

    /**
     * @param string|null $price10
     */
    public function setPrice10(?string $price10): void
    {
        $this->price10 = $price10;
    }

    /**
     * @return string|null
     */
    public function getAnbieter10(): ?string
    {
        return $this->anbieter10;
    }

    /**
     * @param string|null $anbieter10
     */
    public function setAnbieter10(?string $anbieter10): void
    {
        $this->anbieter10 = $anbieter10;
    }

    /**
     * @return string|null
     */
    public function getLz10(): ?string
    {
        return $this->lz10;
    }

    /**
     * @param string|null $lz10
     */
    public function setLz10(?string $lz10): void
    {
        $this->lz10 = $lz10;
    }

    /**
     * @return string
     */
    public function getMeineArtikelnummer(): string
    {
        return $this->meineArtikelnummer;
    }

    /**
     * @param string $meineArtikelnummer
     */
    public function setMeineArtikelnummer(string $meineArtikelnummer): void
    {
        $this->meineArtikelnummer = $meineArtikelnummer;
    }

    /**
     * @return string|null
     */
    public function getGeizhalsArtikelURL(): ?string
    {
        return $this->geizhalsArtikelURL;
    }

    /**
     * @param string|null $geizhalsArtikelURL
     */
    public function setGeizhalsArtikelURL(?string $geizhalsArtikelURL): void
    {
        $this->geizhalsArtikelURL = $geizhalsArtikelURL;
    }


}
