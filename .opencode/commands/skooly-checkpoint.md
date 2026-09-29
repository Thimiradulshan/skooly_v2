---
description: Save Skooly project state before compaction, phase transition, or commit.
---

Use skooly-checkpoint skill.

Inspect:
- AGENTS.md
- .ai/guidelines/skooly-domain.md
- .ai/context/*.md
- git status
- git diff --name-only

Update:
- .ai/context/phase-status.md
- .ai/context/open-business-decisions.md
- .ai/context/current-architecture.md
- .ai/context/verification-history.md
- .ai/context/phase-history.md

Record:
1. Current phase.
2. Completed phases.
3. Files changed.
4. Schema decisions.
5. Model/relationship decisions.
6. Deferred features.
7. Unresolved business decisions.
8. Latest verification result.
9. Current blockers.
10. Next exact step.
11. Whether compacting is safe.

Do not compact automatically.
Ask the user before compaction.
