<?php

namespace App\Support;

final class SecureImageUpload
{
    /**
     * @return array<string, list<string>>
     */
    public static function rules(string $field = 'image', bool $required = false): array
    {
        $mimes = implode(',', config('security.upload.mimes', ['jpeg', 'jpg', 'png', 'webp', 'gif']));
        $max = (int) config('security.upload.max_kb', 5120);

        $rules = array_filter([
            $required ? 'required' : 'nullable',
            'image',
            'mimes:'.$mimes,
            'max:'.$max,
        ]);

        return [$field => $rules];
    }
}
