<?php

namespace Codercwm\Educe\Concerns;

interface InBatches
{
    public function skip(int $num);

    public function take(int $num=200);

    public function count():int;
}