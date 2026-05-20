# Design: Institution Setup Role-Based Visibility Control

We want to restrict access to institution and personnel configuration options on the Setup > Institution page based on the user's role.

## Requirements

1. **Super Admin & Registrar Administrator:**
   - Full read and write access to all Institution Profile settings (Name, Campus, Address, Contact, Email, Website, Exam Name, Exam Acronym).
   - Full read and write access to all Key Personnel (Guidance Counselor, Registrar, College President, Campus Director, VP Academic Affairs, Dean, Testing Coordinator).
   - Able to reset all settings to defaults.

2. **Test Administrator:**
   - No access to the Institution Profile (hidden completely).
   - Access to ONLY Guidance Counselor and Testing Coordinator personnel settings.
   - Able to reset only Guidance Counselor and Testing Coordinator overrides to defaults.
   - Unauthorized fields are filtered out and ignored on updates.

## Technical Design

### 1. Backend: `InstitutionController`

- **`index`:**
  - Check user roles: `$isSuperOrRegistrar = $user->hasAnyRole(['super_admin', 'registrar_administrator']);`
  - If `$isSuperOrRegistrar` is true, pass the complete `$profile` array and all `$personnelRoles` to the Inertia view.
  - If false, pass an empty array (`[]`) for `$profile`, and filter `$personnel` / `$personnelRoles` to only include `['guidance_counselor', 'testing_coordinator']`.

- **`update`:**
  - Check user roles to define allowed fields.
  - If NOT `$isSuperOrRegistrar`, ignore any input under `profile`.
  - Filter `personnel` inputs so only `guidance_counselor` and `testing_coordinator` can be updated.

- **`resetDefaults`:**
  - If `$isSuperOrRegistrar`, delete all keys starting with `institution.`.
  - If false, delete only keys starting with `institution.personnel.guidance_counselor.` and `institution.personnel.testing_coordinator.`.

### 2. Frontend: `resources/js/Pages/Admin/Institution/Index.svelte`

- Check if `profile` has any keys (e.g. `Object.keys(profile).length > 0`). If not, hide the Institution Profile Card completely.
- Ensure the profile loop is only rendered if `profile` keys exist.
- Loop over `personnelRoles` to render key personnel sections dynamically.

### 3. Tests: `tests/Feature/Admin/InstitutionControllerTest.php`

- Add tests verifying `test_administrator` access:
  - Can load the institution page and see only guidance counselor and testing coordinator data, with no profile details.
  - Can update guidance counselor and testing coordinator settings.
  - Cannot update profile settings (ignored / unauthorized).
  - Can reset only their allowed overrides.
