<?php
namespace SnowIO\AttributeSetCode\Test\Integration\Model;

use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Group;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use SnowIO\AttributeSetCode\Api\CodedAttributeSetRepositoryInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterfaceFactory;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterfaceFactory;
use SnowIO\AttributeSetCode\Model\AttributeSetCodeRepository;

class CodedAttributeSetRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateImplicitlyEmptyAttributeSet()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var CodedAttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(CodedAttributeSetRepositoryInterface::class);
        /** @var AttributeSetInterfaceFactory $attributeSetFactory */
        $attributeSetFactory = $objectManager->get(AttributeSetInterfaceFactory::class);

        /** @var AttributeSetInterface $attributeSet */
        $attributeSet = $attributeSetFactory->create()
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product');

        $attributeSetRepository->save($attributeSet);

        self::assertAttributeSetCorrectInDb($attributeSet);
    }

    public function testCreateExplicitlyEmptyAttributeSet()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var CodedAttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(CodedAttributeSetRepositoryInterface::class);
        /** @var AttributeSetInterfaceFactory $attributeSetFactory */
        $attributeSetFactory = $objectManager->get(AttributeSetInterfaceFactory::class);

        /** @var AttributeSetInterface $attributeSet */
        $attributeSet = $attributeSetFactory->create()
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product')
            ->setAttributeGroups([]);

        $attributeSetRepository->save($attributeSet);

        self::assertAttributeSetCorrectInDb($attributeSet);
    }

    public function testCreateAttributeSetWithImplicitlyEmptyAttributeGroups()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var CodedAttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(CodedAttributeSetRepositoryInterface::class);
        $attributeSetFactory = $objectManager->get(AttributeSetInterfaceFactory::class);
        $attributeGroupFactory = $objectManager->get(AttributeGroupInterfaceFactory::class);

        /** @var AttributeSetInterface $attributeSet */
        $attributeSet = $attributeSetFactory->create()
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product')
            ->setAttributeGroups([
                $attributeGroupFactory->create()
                    ->getAttributeGroupCode('my-test-attribute-group-1')
                    ->setName('My Test Attribute Group 1'),
                $attributeGroupFactory->create()
                    ->getAttributeGroupCode('my-test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
            ]);

        $attributeSetRepository->save($attributeSet);

        self::assertAttributeSetCorrectInDb($attributeSet);
    }

    public function testCreateAttributeSetWithExplicitlyEmptyAttributeGroups()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var CodedAttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(CodedAttributeSetRepositoryInterface::class);
        $attributeSetFactory = $objectManager->get(AttributeSetInterfaceFactory::class);
        $attributeGroupFactory = $objectManager->get(AttributeGroupInterfaceFactory::class);

        /** @var AttributeSetInterface $attributeSet */
        $attributeSet = $attributeSetFactory->create()
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product')
            ->setAttributeGroups([
                $attributeGroupFactory->create()
                    ->getAttributeGroupCode('my-test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([]),
                $attributeGroupFactory->create()
                    ->getAttributeGroupCode('my-test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([])
            ]);

        $attributeSetRepository->save($attributeSet);



        self::assertAttributeSetCorrectInDb($attributeSet);
    }

    private static function assertAttributeSetCorrectInDb(AttributeSetInterface $expectedAttributeSet)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(AttributeSetRepositoryInterface::class);
        /** @var AttributeSetCodeRepository $attributeSetCodeRepository */
        $attributeSetCodeRepository = $objectManager->get(AttributeSetCodeRepository::class);
        $expectedEntityTypeId = self::getEntityTypeId($expectedAttributeSet->getEntityTypeCode());
        $attributeSetId = $attributeSetCodeRepository->getAttributeSetId($expectedEntityTypeId, $expectedAttributeSet->getAttributeSetCode());
        self::assertNotNull($attributeSetId);
        $actualAttributeSet = $attributeSetRepository->get($attributeSetId);

        self::assertAttributeSetAsExpected($expectedAttributeSet, $actualAttributeSet);
    }

    private static function assertAttributeSetAsExpected(AttributeSetInterface $expected, \Magento\Eav\Api\Data\AttributeSetInterface $actual)
    {
        $expectedEntityTypeId = self::getEntityTypeId($expected->getEntityTypeCode());
        self::assertSame($expectedEntityTypeId, $actual->getEntityTypeId());

        if ($expected->getName() !== null) {
            self::assertSame($expected->getName(), $actual->getAttributeSetName());
        }

        $expectedAttributeGroups = $expected->getAttributeGroups();
        if ($expectedAttributeGroups !== null) {
            self::assertAttributeGroupsAsExpected($expectedAttributeGroups, $actual->getAttributeSetId());
        }
    }

    private static function assertAttributeGroupsAsExpected(array $expectedGroups, string $actualAttributeSetId)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeGroupRepositoryInterface $attributeGroupRepository */
        $attributeGroupRepository = $objectManager->get(AttributeGroupRepositoryInterface::class);

        $expectedGroupsByCode = [];
        foreach ($expectedGroups as $expectedGroup) {
            $expectedGroupsByCode[$expectedGroup->getAttributeGroupCode()] = $expectedGroup;
        }

        $searchCriteria = $objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('attribute_set_id', $actualAttributeSetId)
            ->create();
        $actualGroups = $attributeGroupRepository->getList($searchCriteria)->getItems();
        $actualGroupsByCode = [];
        /** @var Group $actualAttributeGroup */
        foreach ($actualGroups as $actualAttributeGroup) {
            $actualGroupsByCode[$actualAttributeGroup->getAttributeGroupCode()] = $actualAttributeGroup;
        }

        self::assertSameSize(
            $expectedGroupsByCode,
            $actualGroupsByCode,
            \sprintf('Attribute set should have %s groups but actually has %s groups.', \count($expectedGroups), \count($actualGroupsByCode))
        );

        foreach ($expectedGroupsByCode as $groupCode => $expectedGroup) {
            self::assertArrayHasKey($groupCode, $actualGroupsByCode, "Attribute set is missing group $groupCode."));
            self::assertAttributeGroupAsExpected($expectedGroup, $actualGroupsByCode[$groupCode]);
        }
    }

    private static function assertAttributeGroupAsExpected(AttributeGroupInterface $expected, Group $actual)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeRepositoryInterface $attributeGroupRepository */
        $attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

        if ($expected->getName() !== null) {
            self::assertSame($expected->getName(), $actual->getAttributeGroupName());
        }

        self::assertSame($expected->getAttributeGroupCode(), $actual->getAttributeGroupCode());

        if ($expected->getSortOrder() !== null) {
            self::assertSame($expected->getSortOrder(), $actual->getSortOrder());
        }

        $expectedAttributeCodes = $expected->getAttributes();
        if ($expectedAttributeCodes !== null) {
            $searchCriteria = $objectManager->create(SearchCriteriaBuilder::class)
                ->addFilter('attribute_set_id', $actual->getAttributeSetId())
                ->addFilter('attribute_group_id', $actual->getAttributeGroupId())
                ->create();
            $actualAttributes = $attributeRepository->getList($searchCriteria)->getItems();
            self::assertSameSize($expected->getAttributes(), $actualAttributes);
            $actualAttributeCodes = \array_map(function (AttributeInterface $attribute) {
                return $attribute->getAttributeCode();
            }, $actualAttributes);
            self::assertSame($expectedAttributeCodes, $actualAttributeCodes);
        }
    }

    private static function getEntityTypeId(string $entityTypeCode): int
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Type $entityType */
        $entityType = $objectManager->create(Type::class)->loadByCode($entityTypeCode);
        return $entityType->getEntityTypeId();
    }g
}
