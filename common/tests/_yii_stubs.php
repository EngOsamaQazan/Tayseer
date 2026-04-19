<?php
/**
 * Minimal Yii base stubs to make FahrasService loadable in standalone
 * smoke tests (no composer / no Yii bootstrap). Production code never
 * uses these — they exist only so we can run `php common/tests/smoke_fahras.php`.
 */

declare(strict_types=1);

namespace yii\base {
    if (!class_exists(__NAMESPACE__ . '\\Component')) {
        class Component {
            public function __construct(array $cfg = []) {
                foreach ($cfg as $k => $v) {
                    if (property_exists($this, $k)) $this->{$k} = $v;
                }
                $this->init();
            }
            public function init(): void {}
        }
    }
    if (!class_exists(__NAMESPACE__ . '\\InvalidConfigException')) {
        class InvalidConfigException extends \Exception {}
    }
}
