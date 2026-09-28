<?php
declare(strict_types=1);

namespace Core;

/**
 * Doğrulayıcı — kural tabanlı, bağımlılıksız.
 *
 * Kurallar dizi olarak verilir: 'required|string|max:160|email'
 * Hata mesajları lang/ dosyasından gelir; admin formlarında alan adıyla
 * birlikte gösterilir.
 */
final class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $labels = [];

    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data   = $data;
        $this->rules  = $rules;
        $this->labels = $labels;
        $this->run();
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules      = is_array($ruleString) ? $ruleString : explode('|', (string) $ruleString);
            $value      = $this->data[$field] ?? null;
            $isRequired = in_array('required', $rules, true);

            $isEmpty = $value === null
                || (is_string($value) && trim($value) === '')
                || (is_array($value) && $value === []);

            if ($isRequired && $isEmpty) {
                $this->addError($field, 'required');
                continue;
            }

            // Zorunlu olmayan alan boşsa DİĞER KURALLAR UYGULANMAZ.
            // (Aksi halde `nullable|url` kuralı boş bir adresi reddeder.)
            if ($isEmpty) {
                continue;
            }

            foreach ($rules as $rule) {
                if (in_array($rule, ['required', 'nullable', ''], true)) {
                    continue;
                }
                [$name, $param] = array_pad(explode(':', (string) $rule, 2), 2, null);
                $this->applyRule($field, $name, $param, $value);

                // İlk hata yeterli — alan bazlı ilerleme
                if (isset($this->errors[$field])) {
                    break;
                }
            }
        }
    }

    private function applyRule(string $field, string $rule, ?string $param, mixed $value): void
    {
        switch ($rule) {
            case 'string':
                if (!is_string($value)) {
                    $this->addError($field, 'string');
                }
                break;

            case 'integer':
                if (!is_int($value) && !preg_match('/^-?\d+$/', (string) $value)) {
                    $this->addError($field, 'integer');
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, 'numeric');
                }
                break;

            case 'boolean':
                break; // checkbox her zaman gönderilmez, normalleştirme controller'da

            case 'array':
                if (!is_array($value)) {
                    $this->addError($field, 'array');
                }
                break;

            case 'email':
                if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email');
                }
                break;

            case 'url':
                if (!filter_var((string) $value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'url');
                }
                break;

            case 'slug':
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value)) {
                    $this->addError($field, 'slug');
                }
                break;

            case 'date':
                if (strtotime((string) $value) === false) {
                    $this->addError($field, 'date');
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $param);
                if (!in_array((string) $value, $allowed, true)) {
                    $this->addError($field, 'in');
                }
                break;

            case 'same':
                if ((string) $value !== (string) ($this->data[$param] ?? '')) {
                    $this->addError($field, 'same');
                }
                break;

            case 'different':
                if ((string) $value === (string) ($this->data[$param] ?? '')) {
                    $this->addError($field, 'different');
                }
                break;

            case 'confirmed':
                if ((string) $value !== (string) ($this->data[$param . '_confirmation'] ?? '')) {
                    $this->addError($field, 'confirmed');
                }
                break;

            case 'password':
                $min = (int) ($param ?: Config::get('security.password.min_length', 12));
                $max = (int) Config::get('security.password.max_length', 200);
                $v   = (string) $value;
                if (mb_strlen($v) < $min) {
                    $this->addError($field, 'min:' . $min);
                } elseif (mb_strlen($v) > $max) {
                    $this->addError($field, 'max:' . $max);
                } elseif (preg_match('/^(?:password|12345678|qwerty|111111|admin|letmein|iloveyou)$/i', $v)) {
                    $this->addError($field, 'weak');
                }
                break;

            case 'min':
                // Sınır DEĞER sayısal olmalıdır; değerin kendisi metin olabilir.
                // (Önceden is_numeric($value) deniyordu — bu yüzden 'name|max:160'
                //  gibi kurallar metinlerde hiç çalışmıyordu.)
                if (is_numeric($param)) {
                    $ok = is_string($value) ? mb_strlen($value) >= (float) $param : (float) $value >= (float) $param;
                    if (!$ok) {
                        $this->addError($field, 'min:' . $param);
                    }
                }
                break;

            case 'max':
                if (is_numeric($param)) {
                    $num = is_numeric($value);
                    $ok  = $num ? ((float) $value <= (float) $param) : (mb_strlen((string) $value) <= (float) $param);
                    if (!$ok) {
                        $this->addError($field, 'max:' . $param);
                    }
                }
                break;

            case 'regex':
                $pattern = $param ?? '';
                // Tehlikeli desenlere izin verme (ReDoS ve şema enjeksiyonu koruması)
                if ($pattern !== '' && !str_contains($pattern, 'e') && @preg_match($pattern, '') !== false) {
                    if (!preg_match($pattern, (string) $value)) {
                        $this->addError($field, 'regex');
                    }
                }
                break;

            case 'phone':
                $digits = preg_replace('/\D/', '', (string) $value) ?? '';
                if (strlen($digits) < 10 || strlen($digits) > 15) {
                    $this->addError($field, 'phone');
                }
                break;

            case 'hexcolor':
                if (!preg_match('/^#?[0-9a-fA-F]{6}$/', (string) $value)) {
                    $this->addError($field, 'hexcolor');
                }
                break;

            case 'locale_key':
                if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', (string) $value)) {
                    $this->addError($field, 'locale_key');
                }
                break;
        }
    }

    private function addError(string $field, string $rule): void
    {
        $this->errors[$field][] = $rule;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string,array<int,string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        $flat = $this->flatErrors();
        return $flat === [] ? null : reset($flat);
    }

    /** @return array<string,string> alan => ilk mesaj */
    public function flatErrors(): array
    {
        $out = [];
        foreach ($this->errors as $field => $rules) {
            $out[$field] = $this->messageFor($field, $rules[0]);
        }
        return $out;
    }

    public function messageFor(string $field, string $rule): string
    {
        $label = $this->labels[$field] ?? Translator::t('validation.field.' . $field, [], ucfirst($field));
        [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, '');

        $key = 'validation.' . $ruleName;
        $message = Translator::t($key);
        if ($message === $key) {
            return "{$label}: geçersiz değer.";
        }

        if (in_array($ruleName, ['min', 'max'], true)) {
            $message = str_replace([':min', ':max'], (string) $param, $message);
        }
        return str_replace([':attribute', ':field'], $label, $message);
    }

    /** Doğrulanan veriyi döndürür (yalnızca kurallarda tanımlı alanlar). */
    public function validated(): array
    {
        $out = [];
        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $out[$field] = $this->data[$field];
            }
        }
        return $out;
    }
}
