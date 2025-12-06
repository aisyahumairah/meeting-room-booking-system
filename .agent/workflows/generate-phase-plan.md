---
description: Create detailed implementation plan for a specific MRBS development phase
---

# Generate Phase Implementation Plan

This workflow creates detailed step-by-step implementation plan files for a specified MRBS development phase.

## Reference Documents (MUST READ FIRST)

Before doing anything, read these reference documents in order:

1. **Development Workflow:** `docs/requirement/workflow.md`
   - Contains the high-level steps for each phase
   - Has database schemas, controller logic, routes
   - This is the PRIMARY source for what to implement

2. **Requirements Document:** `docs/requirement/requirements-document.md`
   - Contains detailed business requirements organized by phase
   - Each section has MUST/SHALL/SHOULD requirements
   - Use section references (e.g., §4.2.1) to link tasks to requirements

3. **Mockup Reference:** `mrbs-mock-up/docs/feature-dev-v2.md`
   - Lists which mockup HTML pages exist and their completion status
   - Shows what UI elements are available

4. **Mockup Pages:** `mrbs-mock-up/pages/`
   - Actual HTML mockup files to convert to Blade templates
   - List the directory to see available files

5. **Example Output:** `docs/workflow/phase1/`
   - This is the format to follow for creating new phase plans
   - Read the README.md and step files to understand the structure

## Phases Available

| Phase | Name | Workflow Section |
|-------|------|------------------|
| 1 | Foundation & Core Layout | Steps 1.1-1.7 |
| 2 | Meeting Rooms Management | Steps 2.1-2.6 |
| 3 | Booking Management | Steps 3.1-3.11 |
| 4 | Administrative Management | Steps 4.1-4.6 |
| 5 | System Quality & Deployment | Steps 5.1-5.5 |

## Workflow Steps

### Step 1: Read All Reference Documents

Read the files listed above. Focus on:
- The specific phase section in `workflow.md`
- The corresponding section in `requirements-document.md`
- The existing `docs/workflow/phase1/` files as format reference

### Step 2: Ask Clarifying Questions

Before creating the plan, ask the user questions to clarify anything that is:
- Ambiguous in the requirements
- Missing from the reference documents
- Related to project-specific decisions not covered in the docs
- Dependencies on previous phases that may affect this phase

Do NOT assume or hallucinate answers. If something is unclear, ASK.

Wait for user responses before proceeding to Step 3.

### Step 3: Create Phase Folder and Files

Create the folder structure:
```
docs/workflow/phase{X}/
├── README.md
└── step-{X}.{N}-{step-name}.md (one file per step from workflow.md)
```

### Step 4: Create README.md

Follow the format from `docs/workflow/phase1/README.md`:
- Phase objective
- Step files table with descriptions and priorities
- Dependency graph showing execution order
- Mockup files to convert
- Database tables created in this phase
- Any test accounts or credentials

### Step 5: Create Step Files

For each step defined in the phase section of `workflow.md`, create ONE markdown file.

Follow the format from `docs/workflow/phase1/step-1.1-database-schema.md`:

**Structure:**
```markdown
# Step X.N: [Step Name]

**Priority:** [CRITICAL/HIGH/MEDIUM] | **Ref:** [§Section] | **Dependencies:** [Previous steps]

---

## Objective
[Brief description of what this step achieves]

---

## Task X.N.1: [Task Name]

[Command or file path]
[Code snippet or detailed instructions]

---

## Task X.N.2: [Task Name]
...

---

## Testing Requirements

[Test file path and test cases]

---

## Acceptance Criteria
- [ ] Criteria 1
- [ ] Criteria 2
...

---

**Next:** [Link to next step file]
```

**Detail Level:** DETAILED - Include:
- Artisan commands to run
- Full file paths
- Code snippets with key logic
- Mockup file references
- Validation rules
- Route definitions

### Step 6: Verify and Confirm

After creating all files:
1. List all created files
2. Confirm the number of steps matches workflow.md for that phase
3. Ask user if modifications are needed

## Important Notes

- Each step = ONE file (do not split steps into multiple files)
- Reference specific mockup files from `mrbs-mock-up/pages/` where applicable
- Use Laravel 12 conventions
- Include acceptance criteria as checkboxes
- Link steps with "Next:" at the bottom of each file
