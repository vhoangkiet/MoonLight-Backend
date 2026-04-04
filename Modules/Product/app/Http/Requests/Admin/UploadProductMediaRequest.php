<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:51200',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime',
            ],
        ];
    }
}
