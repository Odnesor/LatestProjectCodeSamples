<?php

namespace SmartEvaluering\Models;

class User extends Authenticatable
{
    //...
    public function sender(): SmartEvalueringSenderContract {
        return app()->makeWith(SmartEvalueringSenderContract::class, ['userId' => $this->id]);
    }
    //...
}
