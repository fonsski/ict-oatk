<?php

namespace App\Services\EquipmentImport;

use RuntimeException;

/**
 * Файл нельзя разобрать целиком: битый JSON, неизвестная структура,
 * слишком много записей. Сообщение написано для человека и показывается
 * на странице импорта как есть.
 */
class InvalidImportFile extends RuntimeException
{
}
