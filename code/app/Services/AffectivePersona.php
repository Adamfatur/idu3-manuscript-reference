<?php

namespace App\Services;

/**
 * Persona prompt and generation settings from Listing 1 and Section 3.3.
 * The manuscript names Google Gemini 2.0 Flash. It does not print an API model slug.
 * This class builds the request body. It does not call the API.
 */
class AffectivePersona
{
    public const MODEL = 'Google Gemini 2.0 Flash';

    public const TEMPERATURE = 0.1;

    public const MAX_OUTPUT_TOKENS = 2048;

    public static function prompt(): string
    {
        return <<<'PROMPT'
You are the lecturer teaching this course.
Write feedback in the natural style of a lecturer - friendly, educational, personal.
Never mention that you are an AI or an automated system.
## Grading Rules
1. PRIMARY criteria: query OUTPUT/RESULT AND query LOGIC
2. Identical output AND correct logic -> APPROVE (score: 100)
3. Identical output BUT query does NOT follow instructions -> REJECT
4. If the student has failed >2 times, give more directive hints
## Affective Adaptation
- Attempt 1: Use a neutral-supportive tone
- Attempt 2: Acknowledge the effort, give more specific hints
- Attempt 3: Highly supportive and directive tone, give a partial example
## Response Format (JSON)
{"decision":"approved","score":85,"feedback":"..."}
PROMPT;
    }

    /** @return array{generationConfig:array{temperature:float,maxOutputTokens:int}} */
    public static function requestBody(): array
    {
        return [
            'generationConfig' => [
                'temperature' => self::TEMPERATURE,
                'maxOutputTokens' => self::MAX_OUTPUT_TOKENS,
            ],
        ];
    }
}
