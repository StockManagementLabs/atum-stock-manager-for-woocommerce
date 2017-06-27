<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb5eb915d7249c0bad4569c36eaa9393c
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Atum\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Atum\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $classMap = array (
        'Atum\\Addons\\Addons' => __DIR__ . '/../..' . '/classes/Addons/Addons.php',
        'Atum\\Addons\\Updater' => __DIR__ . '/../..' . '/classes/Addons/Updater.php',
        'Atum\\Bootstrap' => __DIR__ . '/../..' . '/classes/Bootstrap.php',
        'Atum\\Components\\AtumException' => __DIR__ . '/../..' . '/classes/Components/AtumException.php',
        'Atum\\Components\\AtumListPage' => __DIR__ . '/../..' . '/classes/Components/AtumListPage.php',
        'Atum\\Components\\AtumListTable' => __DIR__ . '/../..' . '/classes/Components/AtumListTable.php',
        'Atum\\Components\\AtumModel' => __DIR__ . '/../..' . '/classes/Components/AtumModel.php',
        'Atum\\Components\\DashboardWidget' => __DIR__ . '/../..' . '/classes/Components/DashboardWidget.php',
        'Atum\\Components\\HelpPointers' => __DIR__ . '/../..' . '/classes/Components/HelpPointers.php',
        'Atum\\Dashboard\\Statistics' => __DIR__ . '/../..' . '/classes/Dashboard/Statistics.php',
        'Atum\\Inc\\Ajax' => __DIR__ . '/../..' . '/classes/Inc/Ajax.php',
        'Atum\\Inc\\Globals' => __DIR__ . '/../..' . '/classes/Inc/Globals.php',
        'Atum\\Inc\\Helpers' => __DIR__ . '/../..' . '/classes/Inc/Helpers.php',
        'Atum\\Inc\\Main' => __DIR__ . '/../..' . '/classes/Inc/Main.php',
        'Atum\\Inc\\Upgrade' => __DIR__ . '/../..' . '/classes/Inc/Upgrade.php',
        'Atum\\InventoryLogs\\InventoryLogs' => __DIR__ . '/../..' . '/classes/InventoryLogs/InventoryLogs.php',
        'Atum\\InventoryLogs\\Items\\LogItemFee' => __DIR__ . '/../..' . '/classes/InventoryLogs/Items/LogItemFee.php',
        'Atum\\InventoryLogs\\Items\\LogItemProduct' => __DIR__ . '/../..' . '/classes/InventoryLogs/Items/LogItemProduct.php',
        'Atum\\InventoryLogs\\Items\\LogItemShipping' => __DIR__ . '/../..' . '/classes/InventoryLogs/Items/LogItemShipping.php',
        'Atum\\InventoryLogs\\Items\\LogItemTax' => __DIR__ . '/../..' . '/classes/InventoryLogs/Items/LogItemTax.php',
        'Atum\\InventoryLogs\\Items\\LogItemTrait' => __DIR__ . '/../..' . '/classes/Atum/InventoryLogs/Items/LogItemTrait.php',
        'Atum\\InventoryLogs\\Models\\Log' => __DIR__ . '/../..' . '/classes/InventoryLogs/Models/Log.php',
        'Atum\\InventoryLogs\\Models\\LogItemModel' => __DIR__ . '/../..' . '/classes/InventoryLogs/Models/LogItemModel.php',
        'Atum\\Settings\\Settings' => __DIR__ . '/../..' . '/classes/Settings/Settings.php',
        'Atum\\StockCentral\\Inc\\ListTable' => __DIR__ . '/../..' . '/classes/StockCentral/Inc/ListTable.php',
        'Atum\\StockCentral\\StockCentral' => __DIR__ . '/../..' . '/classes/StockCentral/StockCentral.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb5eb915d7249c0bad4569c36eaa9393c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb5eb915d7249c0bad4569c36eaa9393c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb5eb915d7249c0bad4569c36eaa9393c::$classMap;

        }, null, ClassLoader::class);
    }
}
