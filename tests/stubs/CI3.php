<?php

/**
 * Stubs de CI3 para PHPStan.
 * Define las propiedades y métodos magic que CI3 inyecta en runtime
 * para que el análisis estático no reporte errores falsos.
 */

class CI_Base
{
    /** @var CI_DB_query_builder */
    public $db;
    /** @var CI_Session */
    public $session;
    /** @var CI_Input */
    public $input;
    /** @var CI_Output */
    public $output;
    /** @var CI_Config */
    public $config;
    /** @var CI_Lang */
    public $lang;
    /** @var CI_Loader */
    public $load;
    /** @var CI_URI */
    public $uri;
    /** @var CI_Cache */
    public $cache;
    /** @var CI_Email */
    public $email;
    /** @var CI_Form_validation */
    public $form_validation;
    /** @var object */
    public $Settings;

    public function __get(string $name): mixed { return null; }
}

class CI_Model extends CI_Base {}
class CI_Controller extends CI_Base {}

// Stubs de modelos del proyecto
class Pos_model extends CI_Model {}
class Site extends CI_Model {}
class Welcome_model extends CI_Model {}
class Reports_model extends CI_Model {}
class Queue_model extends CI_Model {}

// Stub de CI_DB_query_builder
class CI_DB_query_builder
{
    public function select(string $select = '*', bool $escape = null): static { return $this; }
    public function from(string $from): static { return $this; }
    public function join(string $table, string $cond, string $type = '', bool $escape = null): static { return $this; }
    public function where(mixed $key, mixed $value = null, bool $escape = null): static { return $this; }
    public function where_in(string $key = null, array $values = null, bool $escape = null): static { return $this; }
    public function like(mixed $field, string $match = '', string $side = 'both', bool $escape = null, bool $insensitiveSearch = false): static { return $this; }
    public function or_where(mixed $key, mixed $value = null, bool $escape = null): static { return $this; }
    public function order_by(string $orderby, string $direction = '', bool $escape = null): static { return $this; }
    public function limit(int $value, int $offset = 0): static { return $this; }
    public function offset(int $offset): static { return $this; }
    public function get(string $table = '', int $limit = null, int $offset = null): CI_DB_result { return new CI_DB_result(); }
    public function get_where(string $table = '', mixed $where = null, int $limit = null, int $offset = null): CI_DB_result { return new CI_DB_result(); }
    public function insert(string $table = '', mixed $set = null, bool $escape = null): bool { return true; }
    public function update(string $table = '', mixed $set = null, mixed $where = null, int $limit = null): bool { return true; }
    public function delete(mixed $table = '', mixed $where = '', int $limit = null, bool $reset_data = true): mixed { return true; }
    public function count_all_results(string $table = '', bool $reset = true): int { return 0; }
    public function dbprefix(string $table = ''): string { return 'tec_' . $table; }
    public function insert_id(): int { return 0; }
    public function affected_rows(): int { return 0; }
    public function last_query(): string { return ''; }
    public function query(string $sql, mixed $binds = false, bool $return_object = null): mixed { return null; }
    public function set(mixed $key, string $value = '', bool $escape = null): static { return $this; }
    public function trans_start(bool $test_mode = false): bool { return true; }
    public function trans_complete(): bool { return true; }
    public function trans_status(): bool { return true; }
    public function group_by(mixed $by = null, bool $escape = null): static { return $this; }
    public function having(mixed $key, mixed $value = null, bool $escape = null): static { return $this; }
    public function distinct(bool $val = true): static { return $this; }
}

class CI_DB_result
{
    public function result(string $type = 'object'): array { return []; }
    public function result_array(): array { return []; }
    public function row(int $n = 0, string $type = 'object'): ?object { return null; }
    public function row_array(int $n = 0): array { return []; }
    public function num_rows(): int { return 0; }
    public function free_result(): void {}
}
