<?php

namespace A\Http;

class CurlLogger
{
    static string $SEPARATOR = "\n————————————————————————————————————————————————————————\n";

    public function __construct(private string $dirpath)
    {
        if (!is_dir($this->dirpath))
        {
            throw new \RuntimeException(sprintf('Directory "%s" does not exist.', $this->dirpath));
        }
    }

    public function logSending(CurlTransaction $transaction)
    {
        $request = $transaction->request;
        $message = sprintf(
            "[%s] Sending request %d:%s%s%s",
            date('Y-m-d H:i:s'),
            $transaction->id,
            self::$SEPARATOR,
            (string) $request,
            self::$SEPARATOR,
        );

        $idstr = str_pad($transaction->id, 4, '0', STR_PAD_LEFT);
        file_put_contents($this->dirpath . "/transaction.$idstr.txt", $message, FILE_APPEND);
    }

    public function logDone(CurlTransaction $transaction)
    {
        $response = $transaction->getResponse();
        $message = sprintf(
            "[%s] Received response %d:%s%s%s",
            date('Y-m-d H:i:s'),
            $transaction->id,
            self::$SEPARATOR,
            (string) $response,
            self::$SEPARATOR,
        );

        $idstr = str_pad($transaction->id, 4, '0', STR_PAD_LEFT);
        file_put_contents($this->dirpath . "/transaction.$idstr.txt", $message, FILE_APPEND);
    }

    public static function enable(string $dirpath)
    {
        self::clearDirectory($dirpath);
        $object = new static($dirpath);

        \A\Event\EventDispatcher::instance()->addEventListener('curl.sending', [$object, 'logSending']);
        \A\Event\EventDispatcher::instance()->addEventListener('curl.done', [$object, 'logDone']);
    }


    /**
     * Supprime tous les fichiers (et dossiers si $recursive=true) d’un répertoire
     * en vérifiant systématiquement que le chemin absolu appartient bien au dossier de base.
     *
     * @param string  $dir        Chemin du dossier à vider
     * @param bool    $recursive  true => on supprime aussi le contenu des sous-répertoires
     * @return bool              true si tout s’est bien passé, false sinon
     */
    public static function clearDirectory(string $dir, bool $recursive = false): bool
    {
        // Chemin absolu canonique du dossier cible
        $base = realpath($dir);
        if ($base === false || !is_dir($base)) {
            return false; // dossier inexistant ou non lisible
        }

        // Ajoute un séparateur final pour faciliter la comparaison
        $base = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $iterator = new \DirectoryIterator($base);

        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            $path = $item->getPathname();
            $real = realpath($path);

            // Si realpath échoue ou n’appartient pas au dossier ciblé : on ignore
            if ($real === false || strpos($real, $base) !== 0) {
                continue;
            }

            if ($item->isDir()) {
                if ($recursive) {
                    // Appel récursif pour vider le sous-dossier
                    if (!self::clearDirectory($real, true)) {
                        return false;
                    }
                    // Puis on supprime le dossier vide
                    echo "RMD $real" . PHP_EOL;
                    if (!rmdir($real)) {
                        return false;
                    }
                }
                // Si non récursif : on ignore le sous-dossier
                continue;
            }

            // Ici on a un fichier régulier : suppression
            echo "RMF $real" . PHP_EOL;
            if (!unlink($real)) {
                return false;
            }
        }

        return true;
    }
}
