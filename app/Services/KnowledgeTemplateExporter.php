<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class KnowledgeTemplateExporter
{
    public function toDocx(string $txtPath): string
    {
        $content = file_get_contents($txtPath);
        $lines = preg_split('/\r\n|\r|\n/', $content);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);
        $section = $phpWord->addSection();

        foreach ($lines as $line) {
            if ($line === '') {
                $section->addTextBreak();
                continue;
            }

            if (str_starts_with($line, '## ')) {
                $section->addText(substr($line, 3), ['bold' => true, 'size' => 13]);
            } else {
                $section->addText($line);
            }

            $section->addTextBreak();
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'kb_tpl_') . '.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return $tempPath;
    }
}
