<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ValidationService
{
    
     * Валидация данных с использованием предопределенных правил
     *
     * @param array $data Данные для валидации
     * @param string $ruleset Набор правил для использования
     * @param array $customMessages Пользовательские сообщения об ошибках
     * @return array Валидированные данные
     * @throws \Illuminate\Validation\ValidationException

    public function validate(array $data, string $ruleset, array $customMessages = []): array
    {
        $rules = $this->getRules($ruleset);

        if (empty($rules)) {
            throw new \InvalidArgumentException("Набор правил '{$ruleset}' не найден");
        }

        $validator = Validator::make($data, $rules, $customMessages);

        if ($validator->fails()) {
            $this->logValidationError($ruleset, $validator->errors()->toArray());
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    
     * Валидация данных с пользовательскими правилами
     *
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @param array $customMessages Пользовательские сообщения об ошибках
     * @return array Валидированные данные
     * @throws \Illuminate\Validation\ValidationException

    public function validateCustom(array $data, array $rules, array $customMessages = []): array
    {
        $validator = Validator::make($data, $rules, $customMessages);

        if ($validator->fails()) {
            $this->logValidationError('custom', $validator->errors()->toArray());
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    
     * Безопасная валидация без исключений
     *
     * @param array $data Данные для валидации
     * @param string|array $rules Набор правил или массив правил
     * @param array $customMessages Пользовательские сообщения об ошибках
     * @return array [bool $isValid, array $validatedData, array $errors]

    public function validateSafe(array $data, $rules, array $customMessages = []): array
    {
        try {
            
            if (is_string($rules)) {
                $validatedData = $this->validate($data, $rules, $customMessages);
            } else {
                $validatedData = $this->validateCustom($data, $rules, $customMessages);
            }

            return [true, $validatedData, []];
        } catch (ValidationException $e) {
            return [false, [], $e->errors()];
        } catch (\Exception $e) {
            Log::error('Ошибка валидации: ' . $e->getMessage());
            return [false, [], ['general' => [$e->getMessage()]]];
        }
    }

    
     * Получить предопределенные правила валидации
     *
     * @param string $ruleset Имя набора правил
     * @return array Правила валидации

    protected function getRules(string $ruleset): array
    {
        $allRules = [
            
            'user.create' => [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role_id' => 'required|exists:roles,id',
            ],
            'user.update' => [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email',
                'password' => 'sometimes|nullable|string|min:8|confirmed',
                'role_id' => 'sometimes|exists:roles,id',
                'is_active' => 'sometimes|boolean',
            ],

            
            'ticket.create' => [
                'title' => 'required|string|min:5|max:255',
                'description' => 'required|string|min:10',
                'priority' => 'required|in:low,medium,high,urgent',
                'category_id' => 'required|exists:ticket_categories,id',
                'equipment_id' => 'nullable|exists:equipment,id',
                'room_id' => 'nullable|exists:rooms,id',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,zip|max:10240',
            ],
            'ticket.update' => [
                'title' => 'sometimes|string|min:5|max:255',
                'description' => 'sometimes|string|min:10',
                'status' => 'sometimes|in:open,in_progress,resolved,closed',
                'priority' => 'sometimes|in:low,medium,high,urgent',
                'assigned_to_id' => 'sometimes|nullable|exists:users,id',
                'category_id' => 'sometimes|exists:ticket_categories,id',
                'equipment_id' => 'sometimes|nullable|exists:equipment,id',
                'room_id' => 'sometimes|nullable|exists:rooms,id',
                'resolution' => 'sometimes|nullable|string',
                'attachments' => 'sometimes|nullable|array',
                'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,zip|max:10240',
            ],

            
            'ticket.comment' => [
                'content' => 'required|string|min:2|max:500',
                'ticket_id' => 'required|exists:tickets,id',
                'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,zip|max:10240',
            ],

            
            'equipment.create' => [
                'name' => 'required|string|max:255',
                'type_id' => 'required|exists:equipment_types,id',
                'serial_number' => 'nullable|string|max:100|unique:equipment,serial_number',
                'inventory_number' => 'nullable|string|max:100|unique:equipment,inventory_number',
                'room_id' => 'nullable|exists:rooms,id',
                'status' => 'required|in:working,broken,under_repair,decommissioned',
                'purchase_date' => 'nullable|date',
                'warranty_end_date' => 'nullable|date|after_or_equal:purchase_date',
                'description' => 'nullable|string',
                'manufacturer' => 'nullable|string|max:255',
                'model' => 'nullable|string|max:255',
                'specifications' => 'nullable|array',
                'image' => 'nullable|image|max:2048',
            ],
            'equipment.update' => [
                'name' => 'sometimes|string|max:255',
                'type_id' => 'sometimes|exists:equipment_types,id',
                'serial_number' => 'sometimes|nullable|string|max:100|unique:equipment,serial_number',
                'inventory_number' => 'sometimes|nullable|string|max:100|unique:equipment,inventory_number',
                'room_id' => 'sometimes|nullable|exists:rooms,id',
                'status' => 'sometimes|in:working,broken,under_repair,decommissioned',
                'purchase_date' => 'sometimes|nullable|date',
                'warranty_end_date' => 'sometimes|nullable|date|after_or_equal:purchase_date',
                'description' => 'sometimes|nullable|string',
                'manufacturer' => 'sometimes|nullable|string|max:255',
                'model' => 'sometimes|nullable|string|max:255',
                'specifications' => 'sometimes|nullable|array',
                'image' => 'sometimes|nullable|image|max:2048',
            ],

            
            'room.create' => [
                'number' => 'required|string|max:50|unique:rooms,number',
                'name' => 'nullable|string|max:255',
                'building' => 'nullable|string|max:255',
                'floor' => 'nullable|integer|min:0|max:100',
                'type' => 'required|in:classroom,office,laboratory,conference,other',
                'capacity' => 'nullable|integer|min:0',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
            ],
            'room.update' => [
                'number' => 'sometimes|string|max:50|unique:rooms,number',
                'name' => 'sometimes|nullable|string|max:255',
                'building' => 'sometimes|nullable|string|max:255',
                'floor' => 'sometimes|nullable|integer|min:0|max:100',
                'type' => 'sometimes|in:classroom,office,laboratory,conference,other',
                'capacity' => 'sometimes|nullable|integer|min:0',
                'description' => 'sometimes|nullable|string',
                'is_active' => 'sometimes|boolean',
            ],

            
            'knowledge.create' => [
                'title' => 'required|string|min:5|max:255',
                'content' => 'required|string|min:10',
                'category_id' => 'required|exists:knowledge_categories,id',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'is_published' => 'boolean',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,zip|max:10240',
            ],
            'knowledge.update' => [
                'title' => 'sometimes|string|min:5|max:255',
                'content' => 'sometimes|string|min:10',
                'category_id' => 'sometimes|exists:knowledge_categories,id',
                'tags' => 'sometimes|nullable|array',
                'tags.*' => 'string|max:50',
                'is_published' => 'sometimes|boolean',
                'attachments' => 'sometimes|nullable|array',
                'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,zip|max:10240',
            ],

            
            'search' => [
                'query' => 'required|string|min:2|max:255',
                'type' => 'sometimes|in:all,tickets,equipment,knowledge,rooms,users',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ],

            
            'api.auth' => [
                'email' => 'required|string|email',
                'password' => 'required|string',
                'device_name' => 'sometimes|string|max:255',
            ],

            
            'contact' => [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'subject' => 'required|string|min:5|max:100',
                'message' => 'required|string|min:10',
                'g-recaptcha-response' => 'sometimes|required',
            ],
        ];

        return $allRules[$ruleset] ?? [];
    }

    
     * Логирование ошибок валидации
     *
     * @param string $ruleset Набор правил
     * @param array $errors Ошибки валидации
     * @return void

    protected function logValidationError(string $ruleset, array $errors): void
    {
        Log::info('Ошибка валидации', [
            'ruleset' => $ruleset,
            'errors' => $errors,
            'user_id' => auth()->id() ?? 'guest',
            'ip' => request()->ip(),
        ]);
    }

    
     * Получить стандартные сообщения об ошибках валидации
     *
     * @return array

    public function getDefaultMessages(): array
    {
        return [
            'required' => 'Поле :attribute обязательно для заполнения.',
            'string' => 'Поле :attribute должно быть строкой.',
            'email' => 'Поле :attribute должно быть действительным email-адресом.',
            'unique' => 'Такое значение поля :attribute уже существует.',
            'min' => [
                'numeric' => 'Поле :attribute должно быть не менее :min.',
                'file' => 'Файл :attribute должен быть не менее :min килобайт.',
                'string' => 'Поле :attribute должно содержать не менее :min символов.',
                'array' => 'Поле :attribute должно содержать не менее :min элементов.',
            ],
            'max' => [
                'numeric' => 'Поле :attribute должно быть не более :max.',
                'file' => 'Файл :attribute должен быть не более :max килобайт.',
                'string' => 'Поле :attribute должно содержать не более :max символов.',
                'array' => 'Поле :attribute должно содержать не более :max элементов.',
            ],
            'exists' => 'Выбранное значение для :attribute некорректно.',
            'boolean' => 'Поле :attribute должно иметь значение true или false.',
            'confirmed' => 'Подтверждение поля :attribute не совпадает.',
            'date' => 'Поле :attribute не является допустимой датой.',
            'after_or_equal' => 'Поле :attribute должно быть датой после или равной :date.',
            'in' => 'Выбранное значение для :attribute ошибочно.',
            'array' => 'Поле :attribute должно быть массивом.',
            'file' => 'Поле :attribute должно быть файлом.',
            'image' => 'Поле :attribute должно быть изображением.',
            'mimes' => 'Поле :attribute должно быть файлом одного из следующих типов: :values.',
            'integer' => 'Поле :attribute должно быть целым числом.',
        ];
    }

    
     * Получить переводы для атрибутов валидации
     *
     * @return array

    public function getAttributeTranslations(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email',
            'password' => 'пароль',
            'role_id' => 'роль',
            'is_active' => 'активность',

            'title' => 'заголовок',
            'description' => 'описание',
            'priority' => 'приоритет',
            'category_id' => 'категория',
            'equipment_id' => 'оборудование',
            'room_id' => 'аудитория',
            'attachments' => 'вложения',
            'status' => 'статус',
            'assigned_to_id' => 'ответственный',
            'resolution' => 'решение',

            'content' => 'содержание',
            'ticket_id' => 'заявка',
            'attachment' => 'вложение',

            'type_id' => 'тип',
            'serial_number' => 'серийный номер',
            'inventory_number' => 'инвентарный номер',
            'purchase_date' => 'дата покупки',
            'warranty_end_date' => 'дата окончания гарантии',
            'manufacturer' => 'производитель',
            'model' => 'модель',
            'specifications' => 'характеристики',
            'image' => 'изображение',

            'number' => 'номер',
            'building' => 'здание',
            'floor' => 'этаж',
            'type' => 'тип',
            'capacity' => 'вместимость',

            'tags' => 'теги',
            'is_published' => 'опубликовано',

            'query' => 'запрос',
            'page' => 'страница',
            'per_page' => 'количество на странице',

            'device_name' => 'имя устройства',

            'subject' => 'тема',
            'message' => 'сообщение',
            'g-recaptcha-response' => 'проверка reCAPTCHA',
        ];
    }
}
