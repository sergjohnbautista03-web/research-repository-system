<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class StudentDocumentAccessTest extends TestCase
{
    public function test_approved_active_student_cannot_read_without_semester_enrollment(): void
    {
        $student = new User([
            'role' => 'user',
            'is_active' => true,
            'is_approved' => true,
            'student_id' => '12345678',
            'current_semester_id' => null,
        ]);

        $student->setRelation('currentSemester', null);

        $this->assertFalse($student->canViewFullDocument());
        $this->assertFalse($student->canSubmitResearch());
    }

    public function test_unauthorized_accounts_cannot_read_full_documents(): void
    {
        foreach ([
            ['is_active' => false],
            ['is_approved' => false],
            ['student_id' => null],
            ['student_id' => ''],
            ['role' => 'guest'],
        ] as $restriction) {
            $student = new User(array_merge([
                'role' => 'user',
                'is_active' => true,
                'is_approved' => true,
                'student_id' => '12345678',
            ], $restriction));

            $this->assertFalse($student->canViewFullDocument(), json_encode($restriction));
        }
    }
}
