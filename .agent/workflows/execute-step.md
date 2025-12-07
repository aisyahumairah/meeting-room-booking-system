---
description: Execute a specific implementation step or task from the workflow plan
---

# Execute Implementation Step

This workflow executes a specific step or task from the phase implementation plan.

## Workflow Steps

### Step 1: Understand the Scope

Determine what the user wants to execute:
- **Full Step** (e.g., "step 1.1") → Execute ALL tasks in the step document
- **Single Task** (e.g., "step 1.1.2" or "task 1.1.2") → Execute ONLY that specific task

### Step 2: Gather Context

Before executing, read these files to understand the context:

1. **README.md** in the same directory as the attached step document
   - Understand the phase objective
   - Check dependency graph for prerequisites
   - Note related mockup files and database tables

2. **Reference files mentioned in README**
   - Requirements document sections
   - Workflow document sections
   - Mockup files if relevant to this step

3. **Previous step files** if this step has dependencies
   - Ensure prerequisites are completed

### Step 3: Execute the Step/Task

Execute the implementation as defined in the step document:
- Run the specified commands
- Create/modify the specified files
- Follow the code patterns shown
- Use the mockup references provided

**If executing a full step:** Complete all tasks (1.1.1, 1.1.2, 1.1.3, etc.) in order.

**If executing a single task:** Complete only that specific task.

### Step 4: Verify Completion

Check the acceptance criteria in the step document:
- Run any tests mentioned
- Verify files were created correctly
- Confirm functionality works as expected

### Step 5: Mark as Complete

After successful execution:

1. **Update the step document** - Change checkbox items from `[ ]` to `[x]` for completed criteria

2. **Update the README.md** - If ALL tasks in a step are complete, mark the step as done in the README (if it has a completion tracking section)

## Notes

- Only execute what the user specifically requests
- Do not execute subsequent steps unless asked
- If a task fails, stop and report the issue before continuing
- Ask for clarification if the step instructions are ambiguous
