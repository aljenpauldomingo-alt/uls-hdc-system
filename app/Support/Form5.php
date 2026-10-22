<?php

namespace App\Support;

/**
 * Steps and option lists for HDC Form 5 (Student Cumulative Record).
 * Labels follow docs/form-fields.md section A exactly. Keys are what gets stored.
 */
final class Form5
{
    /**
     * Wizard steps in order. The last step (review) is where the student submits.
     *
     * @var array<string, string>
     */
    public const STEPS = [
        'entry' => 'Entry Information',
        'personal' => 'Personal Data',
        'family' => 'Family Background',
        'health' => 'Health',
        'education' => 'Education',
        'vocational' => 'Vocational',
        'social' => 'Social Functions',
        'self' => 'Self-Description',
        'skills' => 'Skills and Counseling',
        'concerns' => 'Psychological, Relationship and Coping',
        'review' => 'Review and Consent',
    ];

    public const MONTHS = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    public const SEMESTERS = [
        '1st' => '1st Semester',
        '2nd' => '2nd Semester',
        'summer' => 'Summer',
    ];

    public const STUDENT_TYPES = [
        'new' => 'New',
        'transferee' => 'Transferee',
        'returnee' => 'Returnee',
        'second_courser' => '2nd Courser',
        'earning_units' => 'Earning Units',
        'graduate_school' => 'Graduate School',
    ];

    public const SEXES = [
        'male' => 'Male',
        'female' => 'Female',
    ];

    public const CIVIL_STATUSES = [
        'single' => 'Single',
        'married' => 'Married',
    ];

    public const YEAR_LEVELS = [
        1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year', 5 => '5th Year',
    ];

    public const PARENT_RELATIONS = [
        'father' => 'Father',
        'mother' => 'Mother',
    ];

    public const PARENTS_STATUSES = [
        'married_in_church' => 'Married in Church',
        'living_in' => 'Living-in',
        'separated' => 'Separated',
        'working_abroad' => 'Working Abroad',
        'remarried' => 'Remarried',
        'deceased' => 'Deceased',
    ];

    public const SCHOOL_LEVELS = [
        'elementary' => 'Elementary',
        'high_school' => 'High School',
        'college' => 'College',
    ];

    public const SCHOOLING_NATURES = [
        'continuous' => 'Continuous',
        'interrupted' => 'Interrupted',
    ];

    public const HOME_FACILITIES = [
        'books' => 'Books',
        'magazines_journals' => 'Magazines/Journals',
        'television' => 'Television',
        'encyclopedia' => 'Encyclopedia',
        'newspapers' => 'Newspapers',
        'computer' => 'Computer',
        'internet_access' => 'Internet Access',
        'laptop_netbook' => 'Laptop/Netbook',
    ];

    public const COURSE_REASONS = [
        'parents_decision' => "Parent's Decision",
        'personal_choice' => 'Personal Choice',
        'no_other_choice' => 'No other choice',
        'friends_advice' => "Friend's Advice",
        'employability' => 'Employability',
        'prestige_of_uls' => 'Prestige of ULS',
        'dont_know' => "I don't know",
        'career_guidance' => 'Encouraged during career guidance/campaign program in 4th year high school',
        'others' => 'Others',
    ];

    public const SELF_DESCRIPTIONS = [
        'risk_taker' => 'Risk-taker',
        'adventurous' => 'Adventurous',
        'hyperactive_as_child' => 'Hyper-active as a child',
        'verbal_outburst' => 'Has verbal outburst',
        'feeling_of_emptiness' => 'Feeling of emptiness',
        'careful' => 'Careful',
        'dependent' => 'Dependent',
        'conforming' => 'Conforming',
        'serious' => 'Serious',
        'leader' => 'Leader',
        'easily_discouraged' => 'Easily discouraged',
        'attention_seeking' => 'Attention-seeking',
        'cannot_stand_alone' => 'Cannot stand alone',
        'feels_depressed_often' => 'Feels depressed often',
        'wants_to_feel_important' => 'Wants to feel important at all times',
        'lacks_self_confidence' => 'Lacks self-confidence',
        'conservative' => 'Conservative',
        'calm' => 'Calm',
        'timid' => 'Timid',
        'dedicated' => 'Dedicated',
        'idealistic' => 'Idealistic',
        'cheerful' => 'Cheerful',
        'emotional' => 'Emotional',
        'fear_of_embarrassment' => 'Fear of embarrassment',
        'isolate' => 'Isolate',
        'frank' => 'Frank',
        'quiet' => 'Quiet',
        'ambitious' => 'Ambitious',
        'contented' => 'Contented',
        'frequent_mood_changes' => 'Frequent mood changes',
    ];

    public const MIN_SELF_DESCRIPTIONS = 5;

    public const SKILLS = [
        'communication' => 'Communication',
        'decision_making' => 'Decision-making',
        'critical_thinking' => 'Critical Thinking',
        'knowing_oneself' => "Getting to know one's self",
        'leadership' => 'Leadership',
        'study_skills' => 'Study Skills',
        'problem_solving' => 'Problem Solving',
        'relationships' => 'How to handle girlfriend/boyfriend relationship',
        'time_management' => 'Time Management',
        'dealing_with_parents' => 'How to deal with parents',
        'others' => 'Others',
    ];

    /**
     * "Others" Yes/No questions that only Form 5 has (Form 8 just has the specify text).
     * Each one makes its specify field required when answered Yes.
     *
     * @var array<string, string> Yes/No field => specify field
     */
    public const CONCERN_OTHERS = [
        'relationship_others' => 'relationship_other',
        'coping_others' => 'coping_other',
    ];

    /**
     * Concern that makes the psychological concerns "specify" field required (as on the paper form).
     */
    public const CONCERN_SPECIFY_TRIGGER = 'loss_of_significant_others';

    public const MAX_SIBLINGS = 10;

    public const MAX_SCHOOLS = 10;

    public const MAX_ORGANIZATIONS = 10;

    public const FORM_INTRO = 'Please complete this inventory as carefully as you can. The information which you provide will be utilized in helping you make decisions in your school life and career. This information will be kept CONFIDENTIAL.';

    public const CONSENT_TEXT = 'I have read and understood the ULS Data Privacy Notice available at https://uls.edu.ph/privacy_notice, and I acknowledge that the University will process and retain my personal data for the declared purposes and for as long as necessary, in accordance with applicable laws and regulations.';

    /**
     * Steps the student fills in (everything except the final review).
     *
     * @return list<string>
     */
    public static function formSteps(): array
    {
        return array_values(array_diff(array_keys(self::STEPS), ['review']));
    }

    public static function nextStep(string $step): ?string
    {
        $keys = array_keys(self::STEPS);
        $index = array_search($step, $keys, true);

        return $keys[$index + 1] ?? null;
    }

    public static function previousStep(string $step): ?string
    {
        $keys = array_keys(self::STEPS);
        $index = array_search($step, $keys, true);

        return $index > 0 ? $keys[$index - 1] : null;
    }

    /**
     * School year for a date, assuming the year starts in June (e.g. Oct 2026 => "2026-2027").
     */
    public static function schoolYearFor(\DateTimeInterface $date): string
    {
        $year = (int) $date->format('Y');
        $start = (int) $date->format('n') >= 6 ? $year : $year - 1;

        return $start.'-'.($start + 1);
    }

    /**
     * Every Yes/No question on the "Psychological, Relationship and Coping" step. The concern,
     * relationship and coping keys are the same as Form 8's, so the two forms can be compared.
     *
     * @return list<string>
     */
    public static function concernYesNoFields(): array
    {
        return [...Form8::yesNoFields(), ...array_keys(self::CONCERN_OTHERS)];
    }

    /**
     * Map stored keys to their labels for display.
     *
     * @param  array<string, string>  $options
     * @param  array<int, string>|null  $keys
     * @return list<string>
     */
    public static function labels(array $options, ?array $keys): array
    {
        return array_values(array_intersect_key($options, array_flip($keys ?? [])));
    }
}
