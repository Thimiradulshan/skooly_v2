# CRUD and Action Coverage Matrix

Phase 10C-5 audit, updated by Phase 10D-1A. What the web UI supports today.

Statuses used:

- **Yes** — supported today
- **Partial** — supported, but with a real limitation
- **No** — not supported, and no backend block exists
- **Not Applicable** — the module does not need this operation
- **Not Recommended** — deliberately omitted because the data is financial or historical
- **Needs Decision** — blocked on a business decision that has not been made

---

## Foundation

| Module | List | Create | View | Edit | Update | Archive | Delete | Restore | Generate | Confirm | Export/Print | Search/Filter | Status change | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| School Setting | Not Applicable | Not Applicable | Yes | Yes | Yes | Not Applicable | No | Not Applicable | Not Applicable | Not Applicable | Not Applicable | No | Not Applicable | Singleton active-year setting. Its downstream behaviour is intentionally inert |
| Academic Year | Yes | Yes | Yes | Yes | Yes | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | Archive/deactivate deferred: schema has no status field |
| Term | Yes | Yes | Yes | Yes | Yes | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | Archive/deactivate deferred: schema has no status field |
| Grade | Yes | Yes | Yes | Yes | Yes | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | Archive/deactivate deferred: schema has no status field; sequence drives promotion |
| Section | Yes | Yes | Yes | Yes | Yes | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | Archive/deactivate deferred: schema has no status field |
| User | No | No | No | No | No | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | Staff cannot be created without a console |
| Role | No | No | No | No | No | Not Applicable | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | Only three fixed roles. Creating roles ad hoc is risky |
| Subject | No | No | No | No | No | Needs Decision | Needs Decision | Not Applicable | Not Applicable | Not Applicable | No | No | No | Referenced by teacher assignments |
| Teacher Assignment | No | No | No | No | No | Needs Decision | Needs Decision | Not Applicable | Not Applicable | Not Applicable | No | No | No | Blocks teacher section scoping |
| Section-Year Assignment | No | No | No | No | No | Needs Decision | Needs Decision | Not Applicable | Not Applicable | Not Applicable | No | No | No | Same |

---

## Registration

| Module | List | Create | View | Edit | Update | Archive | Delete | Restore | Generate | Confirm | Export/Print | Search/Filter | Status change | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Family | Yes | Yes | Yes | Yes | Yes | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | Partial | No | Index has no search. Delete is blocked by children in the database |
| Guardian | Partial | Partial | No | No | No | Not Applicable | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | Only the one guardian entered at family creation |
| Student | Partial | Yes | No | No | No | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | Reachable only through the family page |
| Guardian-Student link | Partial | Partial | No | No | No | Not Applicable | Needs Decision | Not Applicable | Not Applicable | Not Applicable | No | No | No | Revocation is the key privacy lever and is console-only |
| Enrollment | No | Partial | No | No | No | Not Recommended | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | Partial | No | Created at registration. Cannot be moved afterwards |
| Enrollment placement | No | No | No | No | No | Not Recommended | Not Recommended | Not Applicable | Yes | Not Applicable | No | No | No | Written by `placeIn()`. No UI triggers it |

---

## Fees

| Module | List | Create | View | Edit | Update | Archive | Delete | Restore | Generate | Confirm | Export/Print | Search/Filter | Status change | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Fee Category | Yes | Yes | No | Yes | Yes | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | No detail page; the row is the record |
| Fee Structure | Yes | Yes | No | No | No | Not Applicable | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | Create-only by design. Editing after generation needs a decision |
| Student Fee Subscription | No | Yes | No | No | No | Not Applicable | Needs Decision | Not Applicable | Not Applicable | Not Applicable | No | No | No | Cannot be switched off once granted |
| Discount | No | Yes | No | No | No | Not Applicable | Needs Decision | Not Applicable | Not Applicable | Not Applicable | No | Partial | No | Cannot be reviewed or withdrawn |
| Student Due Item | Partial | No | Partial | No | No | Not Recommended | Not Recommended | Not Applicable | Yes | Not Applicable | No | Yes | Partial | Only via dashboard and the payment form |
| Due Item Discount | No | No | Partial | Not Applicable | Not Applicable | Not Applicable | Not Recommended | Not Applicable | Yes | Not Applicable | No | No | Not Applicable | Immutable snapshot, shown on the receipt |

---

## Payments

| Module | List | Create | View | Edit | Update | Archive | Delete | Restore | Generate | Confirm | Export/Print | Search/Filter | Status change | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Payment | **No** | Yes | Yes | **Needs Decision** | **Needs Decision** | Not Applicable | **Not Recommended** | Not Applicable | Not Applicable | Not Applicable | No | No | Not Applicable | No index page exists. No correction path exists |
| Payment Allocation | No | No | Partial | Not Applicable | Not Applicable | Not Applicable | Not Recommended | Not Applicable | Yes | Not Applicable | No | No | Not Applicable | Immutable, shown on the payment page |
| Receipt | **No** | No | Yes | Not Applicable | Not Applicable | Not Applicable | **Not Recommended** | Not Applicable | Yes | Not Applicable | **Partial** | No | Not Applicable | No index. Print stylesheet exists; PDF is absent |

---

## Events

| Module | List | Create | View | Edit | Update | Archive | Delete | Restore | Generate | Confirm | Export/Print | Search/Filter | Status change | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Event | Yes | Yes | Yes | Yes | Yes | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | No | No discard capability |
| Event Charge | Yes | Yes | Partial | No | No | Not Applicable | Needs Decision | Not Applicable | Not Applicable | Not Applicable | No | No | No | Create-only. Editing after generation would silently re-price |
| Event Participation | Yes | Yes | Partial | No | No | Not Applicable | Needs Decision | Not Applicable | Not Applicable | Not Applicable | No | Partial | Yes | Only charge-grade students are listed |
| Event Due Item | No | No | No | Not Applicable | Not Applicable | Not Applicable | Not Recommended | Not Applicable | Yes | Not Applicable | No | No | Not Applicable | Immutable link table |

---

## Promotion

| Module | List | Create | View | Edit | Update | Archive | Delete | Restore | Generate | Confirm | Export/Print | Search/Filter | Status change | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Promotion Batch | Yes | Yes | Yes | No | No | Needs Decision | Not Recommended | Not Applicable | Not Applicable | Yes | No | No | Yes | `discarded` status exists with no UI |
| Promotion Batch Section | Partial | No | Partial | Not Applicable | Not Applicable | Not Applicable | Not Recommended | Not Applicable | Not Applicable | Not Applicable | No | No | Not Applicable | Read-only projection |
| Promotion Batch Item | Yes | No | Partial | **Needs Decision** | **Needs Decision** | Not Applicable | Not Recommended | Not Applicable | No | Yes | No | No | No | Targets and actions are frozen after creation |
| Promotion reversal | No | No | No | No | No | No | No | No | No | No | No | No | No | Blocked on the unresolved safety window |

---

## Reminders

| Module | List | Create | View | Edit | Update | Archive | Delete | Restore | Generate | Confirm | Export/Print | Search/Filter | Status change | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Payment Reminder | Yes | Yes | Yes | No | No | Not Applicable | Not Recommended | Not Applicable | Yes | Not Applicable | No | Yes | **No** | `cancelled` status exists with no UI, so a stale reminder cannot be withdrawn |
| Reminder sending | No | No | No | No | No | Not Applicable | Not Applicable | Not Applicable | No | No | No | No | No | Correctly absent. No delivery provider is chosen |

---

## Audit, reports, security

| Module | List | Create | View | Edit | Update | Archive | Delete | Restore | Generate | Confirm | Export/Print | Search/Filter | Status change | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Audit Log | **No** | Automatic | **No** | Not Applicable | Not Applicable | Not Applicable | **Not Recommended** | Not Applicable | Not Applicable | Not Applicable | **No** | **No** | Not Applicable | Written on every sensitive action, never readable. Append-only by design |
| Dues Dashboard | Yes | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | No | Yes | Not Applicable | Filters work well |
| Admin Dashboard | Yes | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | No | No | Not Applicable | Counts only |
| Login | Not Applicable | Not Applicable | Yes | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | No | No | Not Applicable | No rate limit, no reset |
| Logout | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | No | No | Not Applicable | Complete |
| Role middleware | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | Not Applicable | No | No | Not Applicable | Admin only today |

---

## The eleven that matter

If only eleven rows were fixed, these are they:

1. Academic Year list and create
2. Grade list and create
3. Section list and create
4. Payment list
5. Receipt list
6. Audit log list and view
7. Guardian add and edit
8. Student detail and edit
9. Discount list and deactivate
10. Payment correction or refund workflow
11. Promotion item editing
