<?php

class NewSerialClass {
    /**
     * Standard-Zeichensatz für die Generierung von Seriennummern
     */
    public const DEFAULT_TOKENS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Standard-Anzahl der Segmente
     */
    public const DEFAULT_NUM_SEGMENTS = 3;

    /**
     * Standard-Anzahl der Zeichen pro Segment
     */
    public const DEFAULT_CHARS_PER_SEGMENT = 5;

    /**
     * Suffix für die Seriennummer (optional)
     * @var string|null
     */
    private ?string $suffix = null;

    /**
     * Anzahl der Segmente
     * @var int
     */
    private int $numSegments = self::DEFAULT_NUM_SEGMENTS;

    /**
     * Anzahl der Zeichen pro Segment
     * @var int
     */
    private int $charsPerSegment = self::DEFAULT_CHARS_PER_SEGMENT;

    /**
     * Zeichensatz für die Seriennummer
     * @var string
     */
    private string $tokens = self::DEFAULT_TOKENS;

    /**
     * Die generierte oder zu verifizierende Seriennummer
     * @var string|null
     */
    private ?string $serialNumber = null;

    /**
     * Factory-Methode zur Erstellung einer neuen Instanz
     *
     * @return self
     */
    public static function instance(): self {
        return new self();
    }

    /**
     * Setzt die Anzahl der Segmente
     *
     * @param int $number Anzahl der Segmente
     * @return self
     * @throws \InvalidArgumentException wenn ungültiger Wert
     */
    public function setSegmentCount(int $number): self {
        if ($number <= 0) {
            throw new \InvalidArgumentException("Die Anzahl der Segmente muss größer als 0 sein");
        }
        $this->numSegments = $number;
        return $this;
    }

    /**
     * Setzt die Anzahl der Zeichen pro Segment
     *
     * @param int $number Anzahl der Zeichen pro Segment
     * @return self
     * @throws \InvalidArgumentException wenn ungültiger Wert
     */
    public function setCharsPerSegment(int $number): self {
        if ($number <= 0) {
            throw new \InvalidArgumentException("Die Anzahl der Zeichen pro Segment muss größer als 0 sein");
        }
        $this->charsPerSegment = $number;
        return $this;
    }

    /**
     * Setzt den Suffix für die Seriennummer
     *
     * @param string $suffix Suffix
     * @return self
     */
    public function setSuffix(string $suffix): self {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * Setzt den Zeichensatz für die Seriennummer
     *
     * @param string $tokens Zeichensatz
     * @return self
     * @throws \InvalidArgumentException wenn ungültiger Wert
     */
    public function setTokens(string $tokens): self {
        if (empty($tokens)) {
            throw new \InvalidArgumentException("Der Zeichensatz darf nicht leer sein");
        }
        $this->tokens = $tokens;
        return $this;
    }

    /**
     * Setzt die zu verifizierende Seriennummer
     *
     * @param string $serial Seriennummer
     * @return self
     */
    public function setSerial(string $serial): self {
        $this->serialNumber = $serial;
        return $this;
    }

    /**
     * Generiert eine Seriennummer mit den konfigurierten Einstellungen
     *
     * @return string Die generierte Seriennummer
     * @throws \Exception wenn Parameter fehlen
     */
    public function generate(): string {
        return $this->serialNumber = self::generateSerial(
            $this->numSegments,
            $this->charsPerSegment,
            $this->suffix,
            $this->tokens
        );
    }

    /**
     * Verifiziert die gesetzte Seriennummer
     *
     * @return bool True wenn die Seriennummer gültig ist, sonst False
     * @throws \Exception wenn keine Seriennummer gesetzt wurde
     */
    public function verify(): bool {
        if ($this->serialNumber === null) {
            throw new \Exception("Es wurde keine Seriennummer zum Verifizieren gesetzt");
        }
        return self::verifySerial($this->serialNumber);
    }

    /**
     * Statische Methode zur Generierung einer Seriennummer
     *
     * @param int $segments Anzahl der Segmente
     * @param int $segmentChars Anzahl der Zeichen pro Segment
     * @param string|null $suffix Optionaler Suffix
     * @param string|null $tokens Optionaler Zeichensatz
     * @return string Die generierte Seriennummer
     */
    public static function generateSerial(
        int $segments = self::DEFAULT_NUM_SEGMENTS,
        int $segmentChars = self::DEFAULT_CHARS_PER_SEGMENT,
        ?string $suffix = null,
        ?string $tokens = null
    ): string {
        // Validierung der Parameter
        if ($segments <= 0 || $segmentChars <= 0) {
            throw new \InvalidArgumentException("Segmente und Zeichen pro Segment müssen größer als 0 sein");
        }

        $tokenSet = $tokens ?? self::DEFAULT_TOKENS;

        if (empty($tokenSet)) {
            throw new \InvalidArgumentException("Der Zeichensatz darf nicht leer sein");
        }

        $tokenLength = strlen($tokenSet);
        $serial = '';

        // Generiere die Segmente
        for ($i = 0; $i < $segments; $i++) {
            $segment = '';
            for ($j = 0; $j < $segmentChars; $j++) {
                // Verwende secure random_int statt rand
                $segment .= $tokenSet[random_int(0, $tokenLength - 1)];
            }
            $serial .= $segment;

            if ($i < ($segments - 1)) {
                $serial .= '-';
            }
        }

        // Füge Suffix hinzu, wenn vorhanden
        if ($suffix !== null) {
            if (is_numeric($suffix)) {
                // Numerischer Suffix
                $serial .= '-' . strtoupper(base_convert($suffix, 10, 36));
            } else {
                // Prüfe, ob es eine IP-Adresse ist
                $long = sprintf("%u", ip2long($suffix));
                if ($suffix === long2ip($long)) {
                    $serial .= '-' . strtoupper(base_convert($long, 10, 36));
                } else {
                    // Anderer String-Suffix
                    $serial .= '-' . strtoupper(str_ireplace(' ', '-', $suffix));
                }
            }
        }

        // Berechne und füge Prüfsumme hinzu
        $checksum = self::calculateChecksum($serial, $segmentChars);
        $serial .= '-' . $checksum;

        return $serial;
    }

    /**
     * Statische Methode zur Verifizierung einer Seriennummer
     *
     * @param string $license Die zu verifizierende Seriennummer
     * @return bool True wenn die Seriennummer gültig ist, sonst False
     */
    public static function verifySerial(string $license): bool {
        // Prüfe, ob die Seriennummer leer ist
        if (empty($license)) {
            return false;
        }

        $segments = explode('-', $license);

        // Prüfe, ob genügend Segmente vorhanden sind
        if (count($segments) < 2) {
            return false;
        }

        $checksum = end($segments);
        array_pop($segments);
        $licenseBase = implode('-', $segments);

        // Berechne Prüfsummenlänge anhand der vorhandenen Prüfsumme
        $checksumLength = strlen($checksum);
        $computedChecksum = self::calculateChecksum($licenseBase, $checksumLength);

        return $checksum === $computedChecksum;
    }

    /**
     * Berechnet die Prüfsumme für eine Seriennummer
     *
     * @param string $input Die Eingabe, für die die Prüfsumme berechnet werden soll
     * @param int $length Die Länge der Prüfsumme
     * @return string Die berechnete Prüfsumme
     */
    private static function calculateChecksum(string $input, int $length): string {
        $checksum = strtoupper(base_convert(md5($input), 16, 36));
        return substr($checksum, 0, $length);
    }
}
