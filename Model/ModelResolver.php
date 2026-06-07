<?php
/**
 * MageOS
 *
 * @category   MageOS
 * @package    MageOS_AdminActivityLog
 * @copyright  Copyright (C) 2018 Kiwi Commerce Ltd (https://kiwicommerce.co.uk/)
 * @copyright  Copyright (C) 2025 MageOS (https://mage-os.org/)
 * @license    https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace MageOS\AdminActivityLog\Model;

use InvalidArgumentException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;
use MageOS\AdminActivityLog\Api\ModelResolverInterface;

/**
 * Model resolver for dynamic model loading
 *
 * Resolves and instantiates models via a DI-injected allowlist that maps a
 * model FQCN to its factory FQCN. The factory is resolved lazily (only when a
 * model of that type is actually saved) — the map holds class-name strings, not
 * eagerly-built factory instances. This keeps the security allowlist (only
 * registered classes are ever instantiated) while staying safe in reduced
 * distributions: an entry for a module that isn't installed simply never
 * resolves, instead of fatally instantiating a missing factory just to build
 * this resolver.
 */
class ModelResolver implements ModelResolverInterface
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param array<class-string, class-string> $modelFactories Map of model FQCN => factory FQCN (factory must expose create())
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly array $modelFactories = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getModel(string $className): AbstractModel
    {
        $className = str_replace('\\Interceptor', '', $className);

        if (!isset($this->modelFactories[$className])) {
            throw new InvalidArgumentException(
                sprintf('Class "%s" is not in the allowed model classes list', $className)
            );
        }

        return $this->objectManager->get($this->modelFactories[$className])->create();
    }

    /**
     * @inheritDoc
     */
    public function loadModel(string $className, int|string $entityId, ?string $field = null): AbstractModel
    {
        $model = $this->getModel($className);

        if ($field !== null) {
            $model->load($entityId, $field);
        } else {
            $model->load($entityId);
        }

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function isValidModelClass(string $className): bool
    {
        return $this->isAllowedModelClass($className);
    }

    /**
     * @inheritDoc
     */
    public function isAllowedModelClass(string $className): bool
    {
        $className = str_replace('\\Interceptor', '', $className);

        return isset($this->modelFactories[$className]);
    }
}
