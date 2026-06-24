<?php

declare(strict_types=1);

/**
 * SmtpMailer - Cliente SMTP mínimo (fsockopen + STARTTLS/SSL + AUTH LOGIN).
 * Sin dependencias: el proyecto no usa Composer. Cubre el caso de uso del
 * sitio: enviar correos de texto plano (confirmación de pedido, reset de
 * contraseña) vía un SMTP autenticado (Gmail, Zoho, SendGrid, etc.).
 *
 * ponytail: SMTP por sockets en vez de PHPMailer para no arrastrar Composer.
 * Si algún día se necesitan adjuntos/HTML multipart, ahí sí conviene PHPMailer.
 */
namespace App\Integracion;

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $from;
    private int $timeout;

    /** @var resource|null */
    private $conn = null;

    public function __construct(string $host, int $port, string $user, string $pass, string $from, int $timeout = 15)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->from = $from !== '' ? $from : $user;
        $this->timeout = $timeout;
    }

    /**
     * Envía un correo de texto plano. Lanza \RuntimeException si el servidor
     * responde con un código inesperado en cualquier paso.
     */
    public function send(string $to, string $subject, string $body): void
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Destinatario inválido.');
        }

        // Puerto 465 = TLS implícito; 587/25 = texto plano + STARTTLS.
        $transport = $this->port === 465 ? 'ssl://' : '';
        $this->conn = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno, $errstr, $this->timeout
        );
        if (!$this->conn) {
            throw new \RuntimeException("No se pudo conectar a SMTP {$this->host}:{$this->port} ({$errstr})");
        }
        stream_set_timeout($this->conn, $this->timeout);

        try {
            $this->expect('220');
            $this->ehlo();

            if ($this->port !== 465) {
                $this->cmd('STARTTLS', '220');
                if (!stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('No se pudo iniciar TLS (STARTTLS).');
                }
                $this->ehlo(); // re-saludar dentro del canal cifrado
            }

            // AUTH LOGIN (usuario y clave en base64)
            $this->cmd('AUTH LOGIN', '334');
            $this->cmd(base64_encode($this->user), '334');
            $this->cmd(base64_encode($this->pass), '235');

            $this->cmd('MAIL FROM:<' . $this->from . '>', '250');
            $this->cmd('RCPT TO:<' . $to . '>', '250');
            $this->cmd('DATA', '354');

            $this->write(self::buildMessage($this->from, $to, $subject, $body) . "\r\n.");
            $this->expect('250');

            $this->cmd('QUIT', '221');
        } finally {
            if (is_resource($this->conn)) {
                fclose($this->conn);
            }
            $this->conn = null;
        }
    }

    /**
     * Arma el mensaje RFC 5322 con cuerpo en base64 (evita dot-stuffing y
     * líneas >998). Pura y testeable: ver self-check al pie.
     */
    public static function buildMessage(string $from, string $to, string $subject, string $body): string
    {
        $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $from,
            'To: ' . $to,
            'Subject: ' . $subjectEnc,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $encodedBody = rtrim(chunk_split(base64_encode($body), 76, "\r\n"));
        return implode("\r\n", $headers) . "\r\n\r\n" . $encodedBody;
    }

    private function ehlo(): void
    {
        $this->cmd('EHLO ' . (gethostname() ?: 'localhost'), '250');
    }

    private function cmd(string $command, string $expectedCode): void
    {
        $this->write($command);
        $this->expect($expectedCode);
    }

    private function write(string $line): void
    {
        fwrite($this->conn, $line . "\r\n");
    }

    /** Lee la respuesta (multilínea) y verifica el código esperado. */
    private function expect(string $code): void
    {
        $response = '';
        while (($line = fgets($this->conn, 515)) !== false) {
            $response .= $line;
            // Las respuestas multilínea usan "250-", la última "250 ".
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if (strncmp($response, $code, strlen($code)) !== 0) {
            throw new \RuntimeException("SMTP esperaba {$code}, respondió: " . trim($response));
        }
    }
}

// --- self-check: php src/Integracion/SmtpMailer.php ---
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $msg = SmtpMailer::buildMessage('a@x.cl', 'b@y.cl', 'Confirmación #5', "Hola\núltima línea");
    assert(str_contains($msg, 'Subject: =?UTF-8?B?' . base64_encode('Confirmación #5') . '?='));
    assert(str_contains($msg, 'Content-Transfer-Encoding: base64'));
    assert(base64_decode(substr($msg, strpos($msg, "\r\n\r\n") + 4)) === "Hola\núltima línea");
    assert(!str_contains($msg, "\n.\r\n")); // sin líneas que rompan el terminador DATA
    echo "SmtpMailer self-check OK\n";
}
