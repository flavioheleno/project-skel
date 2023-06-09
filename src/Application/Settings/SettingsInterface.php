<?php
declare(strict_types = 1);

namespace FlavioHeleno\ProjectSkel\Application\Settings;

interface SettingsInterface {
  public function has(string $entry): bool;

  public function getSection(string $entry): array;

  public function getString(string $entry, string $default = ''): string;

  public function getInt(string $entry, int $default = 0): int;

  public function getFloat(string $entry, float $default = 0.0): float;

  public function getBool(string $entry, bool $default = false): bool;
}
