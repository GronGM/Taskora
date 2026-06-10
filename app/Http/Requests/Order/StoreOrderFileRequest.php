<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreOrderFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && Gate::allows('uploadFile', $order);
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
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,png,jpg,jpeg,webp,zip',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Выберите файл для загрузки.',
            'file.file' => 'Загрузите корректный файл.',
            'file.max' => 'Файл не должен быть больше 20 MB.',
            'file.mimes' => 'Разрешены только pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv, png, jpg, jpeg, webp и zip.',
        ];
    }
}
