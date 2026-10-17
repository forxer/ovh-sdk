<?php

declare(strict_types=1);

arch('strict types')->expect('OvhSdk')->toUseStrictTypes();
arch('framework agnostic core')->expect('OvhSdk')->not->toUse('Illuminate')->ignoring('OvhSdk\Laravel');
arch()->preset()->php();
