---
name: skooly-checkpoint
description: Use before compaction, after each phase, before committing, or when context may be stale. Preserves Skooly project state into .ai/context files.
---

# Skooly Checkpoint Skill

Compaction is not the source of truth.

Source of truth:
- AGENTS.md
- .ai/guidelines/skooly-domain.md
- .ai/context/phase-status.md
- .ai/context/open-business-decisions.md
- .ai/context/current-architecture.md
- .ai/context/verification-history.md
- .ai/context/phase-history.md

Before compaction:
1. Update current phase.
2. Record completed files.
3. Record schema/model decisions.
4. Record deferred features.
5. Record unresolved business decisions.
6. Record verification results.
7. Record blockers.
8. Record next exact step.
9. Ask user before compacting.

After compaction:
1. Reread AGENTS.md.
2. Reread .ai/guidelines/skooly-domain.md.
3. Reread all .ai/context/*.md files.
4. Restate current phase, blockers, and next step.
5. Stop if important facts are missing.

Rules:
- Do not rely only on compacted chat memory.
- Do not invent SRS/business rules.
- Do not mark a phase complete unless verification passed.
- Do not commit unless user approves.
