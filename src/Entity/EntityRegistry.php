<?php

namespace Adilis\SeoOptimizer\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

class EntityRegistry
{
    /** @var array<string, EntityInterface> */
    private static $entities = [];

    /** @var bool */
    private static $initialized = false;

    /**
     * Initialize the registry with built-in entities and fire the hook
     * for external modules to register their own.
     */
    private static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        // Built-in entities
        self::register(new ProductEntity());
        self::register(new CategoryEntity());
        self::register(new CmsEntity());
        self::register(new ManufacturerEntity());
        self::register(new SupplierEntity());

        // Allow other modules to register custom entities
        \Hook::exec('actionSeoOptimizerRegisterEntity', [
            'registry' => new self(),
        ]);
    }

    /**
     * Register an entity type.
     *
     * @param EntityInterface $entity
     */
    public static function register(EntityInterface $entity): void
    {
        self::$entities[$entity->getType()] = $entity;
    }

    /**
     * Get entity by type identifier.
     *
     * @param string $type
     * @return EntityInterface|null
     */
    public static function get(string $type)
    {
        self::init();
        return isset(self::$entities[$type]) ? self::$entities[$type] : null;
    }

    /**
     * Get entity by front controller name.
     *
     * @param string $controller
     * @return EntityInterface|null
     */
    public static function getByController(string $controller)
    {
        self::init();
        foreach (self::$entities as $entity) {
            if ($entity->getController() === $controller) {
                return $entity;
            }
        }
        return null;
    }

    /**
     * Get all registered entities.
     *
     * @return array<string, EntityInterface>
     */
    public static function getAll(): array
    {
        self::init();
        return self::$entities;
    }

    /**
     * Get all entity type identifiers.
     *
     * @return string[]
     */
    public static function getTypes(): array
    {
        self::init();
        return array_keys(self::$entities);
    }

    /**
     * Get tag definitions for all entities (for meta template forms).
     *
     * @return array<string, array<string, string>>
     */
    public static function getTagDefinitions(): array
    {
        self::init();
        $definitions = [];
        foreach (self::$entities as $type => $entity) {
            $definitions[$type] = $entity->getAvailableTags();
        }
        return $definitions;
    }

    /**
     * Get entity labels for forms/display.
     *
     * @return array<string, string>
     */
    public static function getLabels(): array
    {
        self::init();
        $labels = [];
        foreach (self::$entities as $type => $entity) {
            $labels[$type] = $entity->getLabel();
        }
        return $labels;
    }

    /**
     * Reset the registry (for testing purposes).
     */
    public static function reset(): void
    {
        self::$entities = [];
        self::$initialized = false;
    }
}
