<?php

namespace johnhenry\matrixblockanchor\models;

use craft\base\Model;

class Settings extends Model
{
    public string $anchorPrefix= 'blockIdAnchor';

    public function defineRules(): array
    {
        return [
            ['anchorPrefix', 'required'],
        ];
    }
}