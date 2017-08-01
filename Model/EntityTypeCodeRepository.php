<?php
namespace SnowIO\AttributeSetCode\Model;

use Magento\Eav\Model\Entity\TypeFactory;

class EntityTypeCodeRepository
{
    private $entityTypeFactory;

    public function __construct(TypeFactory $entityTypeFactory)
    {
        $this->entityTypeFactory = $entityTypeFactory;
    }

    public function getEntityTypeId(string $code): int
    {
        return $this->entityTypeFactory->create()->loadByCode($code)->getEntityTypeId();
    }

    public function getEntityTypeCode(int $entityTypeId): string
    {
        return $this->entityTypeFactory->create()->load($entityTypeId)->getEntityTypeCode();
    }
}