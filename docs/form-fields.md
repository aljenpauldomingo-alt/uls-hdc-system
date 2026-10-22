# HDC Form Field Specification

Source: scanned paper forms from the University of La Salette (ULS) Human Development Center, Santiago City
(`docs/forms/ULS_HDC_Student_Cumulative_Record_form 1.pdf`, 6 pages).

There are **three forms**. Each one is filled by a different person.

| Form | Pages | Filled by | When |
| --- | --- | --- | --- |
| A. HDC Form 5: Student Cumulative Record | 1–3 | Student | Online, during enrollment |
| B. Entrance Test Result (SATA) | 4 | HDC staff (counselor / director) | After the entrance exam |
| C. HDC Form 8: Intake Interview Form | 5–6 | Counselor, during an intake interview with the student | When a student is interviewed |

Sensitivity levels used below:
- **Normal**: the student and assigned staff can see it.
- **Sensitive**: assigned counselor and director only. Never shown in lists or exports by default.
- **Highly sensitive**: assigned counselor and director only, and only on the single-record view. Used in reports only as aggregate counts.

---

## A. HDC Form 5: Student Cumulative Record (student fills)

Header text to show on the form:
> Please complete this inventory as carefully as you can. The information which you provide will be utilized in helping you make decisions in your school life and career. This information will be kept **CONFIDENTIAL**.

A 2x2 photo box appears top-right on paper. Optional photo upload.

### A0. Entry information
| Field | Type | Options / notes |
| --- | --- | --- |
| Month of entry | select | January–December |
| School year | text | e.g. 2026-2027 |
| Semester | radio | 1st Semester, 2nd Semester, Summer |
| Student type | radio | New, Transferee, Returnee, 2nd Courser, Earning Units, Graduate School |
| Photo | image upload | optional, 2x2 |

### A1. Personal data (Normal, except where marked)
| Field | Type | Notes |
| --- | --- | --- |
| Last name, first name, middle name | text ×3 | |
| Course preference | select | from programs table |
| Major | text | optional |
| Home address | text | house no., street, barangay, municipality, province |
| Age | number | can be computed from birth date |
| Sex | radio | Male, Female |
| Civil status | radio | Single, Married |
| Cell phone / landline no. | text | |
| E-mail address | email | |
| Birth date | date | |
| Place of birth | text | |
| Nationality | text | |
| Religion | text | Sensitive |
| Ethnicity | text | Sensitive |
| Boarding house address | text | optional |
| Emergency contact name | text | |
| Emergency contact address | text | |
| Emergency contact no. | text | |
| Spouse name | text | only if Married |
| No. of children | number | only if Married |

### A2. Family background & socio-economic data (Sensitive)
For **father** and **mother** each:
| Field | Type |
| --- | --- |
| Name | text |
| Age | number |
| Religion | text |
| Occupation | text |
| Monthly income | text or number |
| Educational attainment | text |

| Field | Type | Options / notes |
| --- | --- | --- |
| Parents' status | checkboxes (multi) | Married in Church, Living-in, Separated, Working Abroad, Remarried, Deceased |
| How do you see your family at present? | long text | **Highly sensitive** |
| Any specific concern you are going through right now with regards to your family? | long text | **Highly sensitive** |
| Siblings (including the student) | repeatable rows (up to ~10) | Name, Sex, Age, Yr./Course/School, Occupation/Office |
| Dialect/language spoken at home | text | |

### A3. Health (Sensitive)
| Field | Type | Notes |
| --- | --- | --- |
| Height | text | |
| Weight | text | |
| Any handicap? (specify) | text | optional |
| Normal eyesight | Yes/No | |
| Normal hearing | Yes/No | |
| Any persistent illness? | text | optional |
| Have you been hospitalized? | Yes/No | |
| Reason for hospitalization | text | if Yes |
| Have you had a surgical operation? | Yes/No | |
| Surgery details | text | if Yes |
| Do you have allergies? | Yes/No | |
| Allergy details | text | if Yes |

### A4. Educational / school information
Repeatable for **Elementary**, **High School**, **College**:
| Field | Type |
| --- | --- |
| Name of school | text |
| Place | text |
| Inclusive years | text (e.g. 2014-2020) |
| Grade average | text |

| Field | Type | Options / notes |
| --- | --- | --- |
| Scholarships and honors received | text | |
| Nature of schooling | radio | Continuous, Interrupted |
| If interrupted, why? | long text | if Interrupted |
| Scholarship you wish to apply for | text | |
| Subject(s) you have difficulty with | text | |
| Best-liked subject(s) | text | |
| Educational facilities at home | checkboxes (multi) | books, magazines/journals, television, encyclopedia, newspapers, computer, internet access, laptop/netbook |
| Title of a book you finished reading recently | text | |

### A5. Vocational
| Field | Type | Options / notes |
| --- | --- | --- |
| Reason in choosing your course | checkboxes (multi) | Parent's Decision, Personal Choice, No other choice, Friend's Advice, Employability, Prestige of ULS, I don't know, Encouraged during career guidance/campaign program in 4th year high school, Others |
| Others (specify) | text | if Others |
| Second course alternative | text | |
| Occupation/job seriously considered, and why | long text | |
| Work experience as a student? | Yes/No | |
| When was this? | text | if Yes |
| Specific work experience | text | if Yes |
| What do you like about your work experience? | long text | if Yes |

### A6. Social functions
| Field | Type |
| --- | --- |
| Club/organization memberships | repeatable rows: Organization, Activity participated in |
| Leisure-time activities/hobbies | text |

### A7. Self-description (Highly sensitive), "Please check at least five" (validate min 5)
Checkboxes (multi):
Risk-taker, Adventurous, Hyper-active as a child, Has verbal outburst, Feeling of emptiness, Careful, Dependent, Conforming, Serious, Leader,
Easily discouraged, Attention-seeking, Cannot stand alone, Feels depressed often, Wants to feel important at all times, Lacks self-confidence, Conservative, Calm, Timid, Dedicated,
Idealistic, Cheerful, Emotional, Fear of embarrassment, Isolate, Frank, Quiet, Ambitious, Contented, Frequent mood changes

### A8. Skills to develop through seminar-workshops
Checkboxes (multi): Communication, Decision-making, Critical Thinking, Getting to know one's self, Leadership, Study Skills, Problem Solving, How to handle girlfriend/boyfriend relationship, Time Management, How to deal with parents, Others (specify)

### A9. Counseling willingness
| Field | Type |
| --- | --- |
| Would you be willing to talk with or seek help from a counselor any time regarding your concerns? | Yes/No |
| Why? | text |

### A10. Psychological, relationship and coping (Highly sensitive; added October 2026, not on the paper form)
Wizard step "Psychological, Relationship and Coping", right before "Review and Consent". Uses the **same keys and labels as Form 8 (C2–C4)** so the student's answers can be compared with the counselor's.
Every Yes/No item is required. Note shown at the top: "Your answers are confidential and are only seen by the HDC Director. They help the HDC support you."

| Group | Items (Yes/No each) | Specify field |
| --- | --- | --- |
| Psychological Concerns | the 16 concerns in C2 (eating_disorder … loss_of_significant_others) | `concerns_specify`, required only when Loss of significant others = Yes |
| Relationship Concerns | family, same_sex, peers, significant_others, `relationship_others` ("Others") | `relationship_other`, required only when Others = Yes |
| Coping Strategies | the 9 strategies in C4, `coping_others` ("Others") | `coping_other`, required only when Others = Yes |

- Storage: one nullable boolean column per item on `cumulative_records` (null = not answered); specify fields encrypted.
- Who sees the answers per student: the student (own record) and the **director only**. Counselors see "Restricted to the HDC Director" on the record page and in exports.
- Urgent items (`config('hdc.follow_up_concerns')`: attempted suicide, rape or sexual assault, substance abuse): the student sees a supportive message with the HDC office contact and the NCMH Crisis Hotline 1553 (`config('hdc.student_support')`), and on submit the director gets a notification with the student's name and a link only.
- Reports: "Self-reported concerns (Form 5)" table in the Psychological concerns report (latest submitted/reviewed record, counts under 5 shown as "<5").
- Records submitted before this step existed keep their status; the step is marked complete with no answers ("Not answered yet").

### A11. Data privacy consent (required before submit)
Checkbox, required:
> I have read and understood the ULS Data Privacy Notice available at https://uls.edu.ph/privacy_notice, and I acknowledge that the University will process and retain my personal data for the declared purposes and for as long as necessary, in accordance with applicable laws and regulations.

Store: consent given (bool), consented_at (timestamp), plus typed full name as the "signature over printed name".

---

## B. Entrance Test Result: SATA (staff enters, Sensitive)

Scholastic Abilities Test for Adults (SATA). Linked to a student.

| Field | Type |
| --- | --- |
| Date of examination | date |
| For each subtest: Standard score | number |
| For each subtest: Descriptor | text (e.g. Average, Above Average) |
| Quotient | number |
| Quotient descriptor | text |

Subtests (legend):
| Code | Name |
| --- | --- |
| VR | Verbal Reasoning |
| NVR | Non-verbal Reasoning |
| QR | Quantitative Reasoning |
| RV | Reading Vocabulary |
| RC | Reading Comprehension |
| MC | Math Calculation |
| MA | Math Application |
| WM | Writing Mechanics |
| WC | Writing Composition |

Students can **not** edit this. Whether students may view their own result is to be confirmed with HDC (default: no).

---

## C. HDC Form 8: Intake Interview Form (counselor fills, Highly sensitive)

A student can have many intake interviews over time. Personal data (C1) should be **pre-filled from the student's Form 5** and editable.

### C0. Header
| Field | Type |
| --- | --- |
| Date of entry | date |
| Academic status | text |

### C1. Personal data
Name, Nickname, Course, Complete home address, Age, Sex, Birthdate, Place of birth, Civil status, Spouse name (if married), No. of children, Citizenship (if not Filipino, specify), Religion, Contact no., Boarding house address, Emergency contact name, Relationship, Emergency contact address, Emergency contact no.

### C2. Psychological concerns (Yes/No each). **These are the concerns counted in HDC reports.**
| Key | Label |
| --- | --- |
| eating_disorder | Eating disorder |
| depression | Depression |
| discrimination | Discrimination |
| pregnancy | Pregnancy |
| abortion | Abortion |
| anxiety_stress | Anxiety or stress |
| financial | Financial concerns |
| alcohol_use | Alcohol use |
| attempted_suicide | Attempted suicide |
| substance_abuse | Substance abuse |
| body_dysmorphic | Body dysmorphic disorder |
| miscarriage | Miscarriage |
| self_esteem | Self-esteem |
| sexual_assault | Rape or sexual assault |
| acculturation | Acculturation |
| loss_of_significant_others | Loss of significant others (if yes, specify) |

Plus: "specify" text field.

### C3. Relationship concerns (Yes/No each)
Family, Same sex, Peers, Significant others, Others (specify)

### C4. Coping strategies (Yes/No each)
Praying, Laughing, Drinking, Malling, Listening to music, Crying, Going out with peers, Partying, Shopping, Others (specify)

### C5–C8. Counselor notes (long text, counselor-only)
| Section | Field |
| --- | --- |
| IV | Description of the client |
| V | Presenting concern(s) |
| VI | Relationship with the client during the intake: checkboxes Cooperative, Aggressive, Shy, Hyperactive, Short attention span |
| VII | Conceptualization of the intake session (e.g., degree of disturbance) |
| VIII | Recommendation(s) |

### C9. Sign-off
| Field | Type |
| --- | --- |
| Date and time of intake interview | datetime |
| Interviewer | the logged-in counselor (auto) |
| Interviewee acknowledgment | checkbox or typed name |

---

## Suggested database shape (for Claude Code to refine)

- `cumulative_records`: one per student per school year/semester; scalar fields from Form 5; status (draft, submitted, reviewed); submitted_at; reviewed_by; reviewed_at.
- `record_parents`: record_id, relation (father/mother), name, age, religion, occupation, monthly_income, educational_attainment.
- `record_siblings`: record_id, name, sex, age, year_course_school, occupation.
- `record_schools`: record_id, level (elementary/high_school/college), school_name, place, inclusive_years, grade_average.
- `record_organizations`: record_id, organization, activity.
- Multi-select checklists (parents' status, facilities, course reasons, self-description, skills): JSON array columns cast to `array`.
- `entrance_test_results`: student_id, exam_date, one score + descriptor column per subtest, quotient, quotient_descriptor, encoded_by.
- `intake_interviews`: student_id, counselor_id, interview_at, personal-data snapshot, **one boolean column per psychological concern** (so reports can COUNT them), relationship concerns, coping strategies, counselor notes.

## Privacy rules
- Sensitive and highly sensitive fields: visible only to the director and to counselors assigned to the student's college.
- Intake interviews (Form 8) and SATA results are never visible to students by default.
- Reports show **counts only** (per concern, per college, per year level). If a count is between 1 and 4, show "fewer than 5" to avoid identifying students.
- Log every view of a full record (who, when) in an `access_logs` table.
- Encrypt highly sensitive long-text fields at rest using Laravel's `encrypted` cast.
