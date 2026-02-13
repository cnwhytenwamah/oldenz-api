<?php

namespace App\Dto;

abstract readonly class BaseDto
{
    /**
     * Convert selected fields to an array, excluding nulls.
     */
    protected function extractToArray(array $data): array
    {
        return array_filter($data, function($value){
            return $value !== null;
        });
    }
}
