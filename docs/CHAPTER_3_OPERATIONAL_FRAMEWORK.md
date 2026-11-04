# Chapter 3: Operational Framework & User Administration

## Moderated Citizen Onboarding

AGOS uses a moderated onboarding pipeline to protect the integrity of citizen-submitted disaster information while keeping registration accessible. A citizen registers through the email/password channel (and, where enabled, the verified Google OAuth channel) by providing a name, email address, and selected barangay. Physical identification and proof-of-residency documents are not collected during registration. The selected `barangay_id` establishes the administrative scope for review.

Every newly created citizen account is assigned `pending` status. The account cannot receive a Sanctum token or enter protected API resources while pending. A login attempt returns HTTP 403 and identifies the selected barangay as the reviewing authority. A Barangay Admin reviews only pending citizen registrations assigned to the administrator's own barangay. The administrator may approve the account or decline it with an optional reason. Approval changes the account to `approved`; decline changes it to `declined` and retains the reason for the user's notice and audit context.

Approval and decline notifications are queued so moderation requests are not delayed by mail delivery. Approved citizens receive instructions to log in. Declined citizens receive the recorded reason, when supplied, and instructions to contact their barangay office. This workflow prevents unreviewed accounts from creating reports or consuming citizen-only API services.

## Operational Roles and Access Control

The workflow applies role-based access control (RBAC) at both the role and barangay boundary. Super Admins manage system-wide administrative assignments. Barangay Admins can list, approve, and decline only citizen accounts whose `barangay_id` matches the administrator's assigned barangay. The authorization policy checks the administrator role, the target citizen role, the pending state, and the barangay equality before any state transition. API routes additionally require Sanctum authentication and the approved-account middleware.

The `EnsureAccountIsApproved` middleware is a defense-in-depth control: token issuance is denied for non-approved citizen accounts, and an account that later becomes declined cannot access approved-only API resources with an existing token. The moderation endpoints are protected by both this middleware and the Barangay Admin role middleware. This ensures that a valid credential alone cannot bypass the organizational boundary.

## Data Privacy and RA 10173 Safeguards

The onboarding process follows the principles of Republic Act No. 10173 (Data Privacy Act of 2012): transparency, legitimate purpose, and proportionality. AGOS collects only the account data required to identify the citizen, deliver account notices, and assign the correct barangay review authority. No physical ID or proof-of-residency upload is required, reducing the volume and sensitivity of stored personal data.

Passwords are stored using Laravel's password hashing cast and are never returned in API responses. Rejection reasons are limited to operational review information, and notification content does not expose another citizen's data. Barangay-level query scoping and policy checks prevent administrators from viewing or modifying registrations outside their mandate. Production deployments should supplement these application controls with least-privilege database credentials, encrypted transport, restricted log access, retention and deletion procedures, and documented data-subject request handling.

The workflow also supports accountability: status transitions, the reviewing administrative identity, timestamps, and notification delivery should be retained in the platform's operational audit records when the production audit subsystem is enabled. Access to those records must follow the same least-privilege and barangay governance rules.
