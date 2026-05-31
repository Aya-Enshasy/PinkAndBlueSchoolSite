<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LearningUnitController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $lessons = Lesson::query()
            ->with('blocks')
            ->where('status', 'published')
            ->orderBy('grade')
            ->orderBy('subject')
            ->orderBy('unit_no')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $units = $lessons
            ->groupBy(fn (Lesson $lesson) => "{$lesson->grade}:{$lesson->subject}:{$lesson->unit_no}")
            ->map(fn (Collection $group) => $this->unitPayload($group))
            ->values();

        return response()->json($units);
    }

    private function unitPayload(Collection $lessons): array
    {
        /** @var Lesson $first */
        $first = $lessons->first();

        return [
            'id' => "db-grade-{$first->grade}-{$first->subject}-unit-{$first->unit_no}",
            'grade' => $first->grade,
            'subject' => $first->subject,
            'unitNo' => $first->unit_no,
            'title' => $first->unit_title ?: "الوحدة {$first->unit_no}",
            'lessons' => $lessons->map(fn (Lesson $lesson) => $this->lessonPayload($lesson))->values(),
        ];
    }

    private function lessonPayload(Lesson $lesson): array
    {
        $blocks = $lesson->blocks->map(fn (LessonBlock $block) => $this->blockPayload($block))->values();
        $exampleBlocks = $lesson->blocks
            ->filter(fn (LessonBlock $block) => in_array($block->type, ['example', 'solved_example', 'gradual_solution', 'complete_solution', 'missing_solution'], true))
            ->values();
        $questionBlocks = $lesson->blocks
            ->filter(fn (LessonBlock $block) => $block->is_graded || in_array($block->type, ['multiple_choice', 'true_false', 'fill_blank', 'match_term', 'match_meaning', 'match_picture', 'order_words', 'order_sentence', 'order_sentence_en', 'order_solution_steps', 'choose_operation', 'choose_heard'], true))
            ->values();

        return [
            'id' => "db-lesson-{$lesson->id}",
            'title' => $lesson->title,
            'xp' => $lesson->xp_reward,
            'theory' => [
                'title' => $lesson->title,
                'body' => '',
                'blocks' => $blocks,
                'points' => ['اقرأ الشرح خطوة خطوة.', 'تفاعل مع الوسائط.', 'انتقل للتطبيق عندما تنتهي.'],
            ],
            'examples' => $exampleBlocks->map(fn (LessonBlock $block, int $index) => $this->examplePayload($block, $index))->values(),
            'worksheet' => $this->questionsPayload($questionBlocks, 'ورقة عمل'),
            'quiz' => $this->questionsPayload($questionBlocks, 'اختبار قصير'),
        ];
    }

    private function blockPayload(LessonBlock $block): array
    {
        $content = $block->content ?: [];
        $media = $block->media ?: [];

        return [
            'type' => $this->studentTheoryType($block->type),
            'originalType' => $block->type,
            'category' => $block->category,
            'layout' => $block->subtype,
            'isGraded' => $block->is_graded,
            'emoji' => $content['emoji'] ?? $this->typeEmoji($block->type),
            'title' => $block->title ?: ($content['term'] ?? 'شرح الدرس'),
            'body' => trim(strip_tags((string) ($content['body'] ?? ''))),
            'term' => $content['term'] ?? '',
            'symbol' => $content['symbol'] ?? '',
            'question' => $content['question'] ?? '',
            'items' => $content['items_text'] ?? implode("\n", $content['items'] ?? []),
            'options' => $this->lines($content['options_text'] ?? $content['items_text'] ?? ''),
            'leftItems' => $content['left_items_text'] ?? '',
            'rightItems' => $content['right_items_text'] ?? '',
            'pairs' => $content['pairs'] ?? [],
            'answer' => $content['answer'] ?? '',
            'score' => (int) ($content['score'] ?? 1),
            'result' => $content['result'] ?? $content['answer'] ?? '',
            'note' => $content['hint'] ?? $content['note'] ?? '',
            'imageUrl' => $media['image_url'] ?? '',
            'url' => $this->firstMediaUrl($media, ['video_url', 'pdf_url', 'audio_url']),
        ];
    }

    private function firstMediaUrl(array $media, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($media[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function examplePayload(LessonBlock $block, int $index): array
    {
        $content = $block->content ?: [];
        $steps = $this->lines($content['items_text'] ?? '');

        return [
            'title' => $block->title ?: 'مثال '.($index + 1),
            'prompt' => trim(strip_tags((string) ($content['body'] ?? $content['prompt'] ?? 'تطبيق سريع على الفكرة.'))),
            'steps' => $steps ?: ['اقرأ المثال.', $content['answer'] ?? 'أضف خطوات الحل من لوحة المعلم.'],
        ];
    }

    private function questionsPayload(Collection $blocks, string $label): array
    {
        $questions = $blocks->map(function (LessonBlock $block) use ($label) {
            $content = $block->content ?: [];
            $options = $this->lines($content['options_text'] ?? $content['items_text'] ?? $content['left_items_text'] ?? '');
            $answer = $content['answer'] ?? $content['right_items_text'] ?? ($options[0] ?? 'نعم');

            return [
                'question' => $content['question'] ?? $block->title ?? $label,
                'options' => $options ?: ['نعم', 'أحتاج مراجعة'],
                'answer' => is_string($answer) && str_contains($answer, "\n") ? $this->lines($answer)[0] ?? 'نعم' : $answer,
                'score' => (int) ($content['score'] ?? 1),
            ];
        })->values()->all();

        return $questions ?: [[
            'question' => $label === 'اختبار قصير' ? 'هل فهمت فكرة الدرس؟' : 'طبّق الفكرة التي تعلمتها.',
            'options' => ['نعم', 'أحتاج مراجعة'],
            'answer' => 'نعم',
            'score' => 1,
        ]];
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', $value) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function studentTheoryType(string $type): string
    {
        if (Str::contains($type, ['audio', 'listen', 'repeat', 'recording', 'read_after_audio'])) {
            return 'audio';
        }

        if (Str::contains($type, ['write', 'describe', 'typing'])) {
            return 'writing';
        }

        if (Str::contains($type, ['video', 'youtube', 'listening'])) {
            return 'youtube';
        }

        if (Str::contains($type, ['pdf'])) {
            return 'pdf';
        }

        if (Str::contains($type, ['match', 'synonym', 'opposite'])) {
            return 'matching';
        }

        if (Str::contains($type, ['order', 'drag', 'build_word'])) {
            return 'ordering';
        }

        if (Str::contains($type, ['choice', 'true_false', 'blank', 'correct', 'complete', 'choose'])) {
            return 'choice';
        }

        if (Str::contains($type, ['definition', 'concept', 'law', 'rule', 'vocabulary', 'word'])) {
            return 'definition';
        }

        if (Str::contains($type, ['example', 'solution', 'experiment'])) {
            return 'example';
        }

        if (Str::contains($type, ['tip', 'mistake', 'conclusion', 'result'])) {
            return 'tip';
        }

        if (Str::contains($type, ['idea', 'grammar', 'explanation'])) {
            return 'idea';
        }

        return 'hook';
    }

    private function typeEmoji(string $type): string
    {
        return match (true) {
            Str::contains($type, ['science', 'experiment', 'observation']) => '',
            Str::contains($type, ['math', 'solution', 'law', 'rule']) => '',
            Str::contains($type, ['english', 'vocabulary', 'listening']) => '',
            Str::contains($type, ['arabic', 'reading', 'grammar']) => '',
            Str::contains($type, ['video']) => '',
            Str::contains($type, ['pdf']) => '',
            default => '',
        };
    }
}
