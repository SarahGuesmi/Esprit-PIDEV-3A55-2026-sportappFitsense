# Fix: Workout Exercises Not Loading & Profile Setup Required

## Problem 1: "Content missing" on My Workouts page
When users accessed "My Workouts", they saw "Content missing" instead of workout cards.

## Root Cause 1
Users without a ProfilePhysique (physical profile) have no objectives, and the system filters workouts by user objectives. Without objectives, no workouts are shown.

## Solution 1
Added a check in `UserWorkoutController::myWorkouts()` to redirect users without a profile to the profile setup wizard:

```php
// Check if user has a profile with objectives
if ($user->getProfilesPhysiques()->isEmpty()) {
    $this->addFlash('info', 'Please complete your profile to see personalized workouts.');
    return $this->redirectToRoute('profile_setup_height');
}
```

## Problem 2: Exercises not loading when viewing a workout
When users clicked "Start Workout", the exercises were not displaying properly.

## Root Cause 2
The `findWithExercises()` method in `WorkoutRepository` was using `find()` followed by `toArray()` to initialize lazy collections. This approach didn't properly load the relationships in all cases.

## Solution 2
Changed the `findWithExercises()` method to use a proper DQL query with explicit JOINs:

```php
public function findWithExercises(string $id): ?Workout
{
    return $this->createQueryBuilder('w')
        ->leftJoin('w.exercises', 'e')->addSelect('e')
        ->leftJoin('w.objectifs', 'o')->addSelect('o')
        ->where('w.id = :id')
        ->setParameter('id', $id)
        ->getQuery()
        ->getOneOrNullResult();
}
```

## Files Modified
- `src/Controller/UserWorkoutController.php` - Added profile check
- `src/Repository/WorkoutRepository.php` - Fixed exercise loading

## Testing
1. Clear cache: `php bin/console cache:clear`
2. Login as a user WITHOUT a profile (e.g., sarahguesmi223@gmail.com)
3. Go to "My Workouts" - should redirect to profile setup
4. Complete the profile setup (height, weight, gender, objectives)
5. Return to "My Workouts" - workouts matching your objectives should now appear
6. Click on "Start Workout" - exercises should display correctly

## Users with Profiles (for testing)
- `omarguesmi@gmail.com` - Has profile with "Weight Loss" and "Well-being" objectives
- `mohamed@gmail.com` - Has profile with "Muscle Gain" and "Endurance" objectives

## Status
✅ Fixed - Users are now guided to create a profile before accessing workouts
✅ Fixed - Exercises now load properly when viewing a workout
