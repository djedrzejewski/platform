<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\ORM\Dbal;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Event\EntityWrittenEvent;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\Tax\TaxDefinition;

class ManyToManyAssociationFieldTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var RepositoryInterface
     */
    private $productRepository;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var RepositoryInterface
     */
    private $categoryRepository;

    protected function setUp()
    {
        $this->productRepository = $this->getContainer()->get('product.repository');
        $this->categoryRepository = $this->getContainer()->get('category.repository');
        $this->context = Context::createDefaultContext(Defaults::TENANT_ID);
    }

    public function testWriteWithoutData()
    {
        $categoryId = Uuid::uuid4();
        $data = [
            'id' => $categoryId->getHex(),
            'name' => 'test',
        ];

        $this->categoryRepository->create([$data], $this->context);

        $productId = Uuid::uuid4();
        $data = [
            'id' => $productId->getHex(),
            'name' => 'test',
            'price' => ['gross' => 15, 'net' => 10],
            'manufacturer' => ['name' => 'test'],
            'tax' => ['name' => 'test', 'taxRate' => 15],
            'categories' => [
                ['id' => $categoryId->getHex()],
            ],
        ];

        $writtenEvent = $this->productRepository->create([$data], $this->context);

        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(TaxDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductManufacturerDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductCategoryDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductManufacturerTranslationDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductTranslationDefinition::class));
        static::assertNull($writtenEvent->getEventByDefinition(CategoryDefinition::class));
        static::assertNull($writtenEvent->getEventByDefinition(CategoryTranslationDefinition::class));
    }

    public function testWriteWithData()
    {
        $id = Uuid::uuid4();
        $data = [
            'id' => $id->getHex(),
            'name' => 'test',
            'price' => ['gross' => 15, 'net' => 10],
            'manufacturer' => ['name' => 'test'],
            'tax' => ['name' => 'test', 'taxRate' => 15],
            'categories' => [
                ['id' => $id->getHex(), 'name' => 'asd'],
            ],
        ];

        $writtenEvent = $this->productRepository->create([$data], $this->context);

        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(TaxDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(CategoryDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(CategoryTranslationDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductManufacturerDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductManufacturerTranslationDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductCategoryDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductDefinition::class));
        static::assertInstanceOf(EntityWrittenEvent::class, $writtenEvent->getEventByDefinition(ProductTranslationDefinition::class));
    }

    private function containsInstance(string $needle, array $haystack): bool
    {
        foreach ($haystack as $element) {
            if ($element instanceof $needle) {
                return true;
            }
        }

        return false;
    }
}
