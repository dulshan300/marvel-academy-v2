<?php

namespace App\Actions\Quizzes;

use App\Models\QuestionOption;
use App\Models\QuizAttempt;

class SaveQuizAnswers
{
    /**
     * Seconds after the deadline during which answers sent just before time
     * ran out are still accepted.
     */
    public const GRACE_SECONDS = 30;

    /**
     * Autosave answers on an attempt in progress, without marking them.
     *
     * Answers for questions outside the quiz, or options that do not belong
     * to their question, are ignored; a null option clears the answer.
     * Returns the number of answers saved.
     *
     * @param  array<int|string, int|string|null>  $answers  question ID => option ID
     */
    public function handle(QuizAttempt $attempt, array $answers): int
    {
        if ($attempt->isComplete() || $attempt->hasExpired(self::GRACE_SECONDS) || $answers === []) {
            return 0;
        }

        $questionIds = array_map('intval', array_keys($answers));

        $validOptions = QuestionOption::query()
            ->whereIn('question_id', $attempt->quiz->questions()->whereKey($questionIds)->pluck('id'))
            ->pluck('question_id', 'id');

        $saved = 0;

        foreach ($answers as $questionId => $optionId) {
            $questionId = (int) $questionId;
            $optionId = filled($optionId) ? (int) $optionId : null;

            if ($optionId !== null && $validOptions->get($optionId) !== $questionId) {
                continue;
            }

            if ($optionId === null && ! $validOptions->contains($questionId)) {
                continue;
            }

            $attempt->answers()->updateOrCreate(
                ['question_id' => $questionId],
                ['question_option_id' => $optionId, 'is_correct' => false, 'marks_awarded' => 0],
            );

            $saved++;
        }

        if ($saved > 0) {
            $attempt->touch();
        }

        return $saved;
    }
}
