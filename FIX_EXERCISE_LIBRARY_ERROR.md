# Fix: Erreur Exercise Library - Paramètre Manquant

## 🔴 Problème

Erreur lors du clic sur un exercice dans la bibliothèque:
```
An exception has been thrown during the rendering of a template 
("Some mandatory parameters are missing ("exerciseId") to generate a URL 
for route "coach_exercise_detail".") 
in coach/exercises.html.twig at line 309.
```

## 🔍 Cause

### Contexte:
Il existe deux routes pour afficher les détails d'un exercice:

1. **Route API** (exercices de RapidAPI):
   ```php
   #[Route('/api-exercises/{exerciseId}', name: 'coach_exercise_detail')]
   ```
   - Attend le paramètre: `exerciseId`
   - Pour les exercices de l'API externe

2. **Route DB** (exercices de la base de données):
   ```php
   #[Route('/exercises/{id}', name: 'coach_exercise_detail_db')]
   ```
   - Attend le paramètre: `id`
   - Pour les exercices créés par le coach

### Problème dans le Template:
**Ligne 309 de `templates/coach/exercises.html.twig`**:
```twig
{# ❌ INCORRECT #}
<a href="{{ path('coach_exercise_detail', {id: ex.id}) }}">
```

- Utilise la route `coach_exercise_detail` (API)
- Mais passe le paramètre `id` au lieu de `exerciseId`
- Les exercices affichés viennent de l'API RapidAPI
- Donc il faut utiliser `exerciseId`

## ✅ Solution Appliquée

### Correction du Template

**Fichier**: `templates/coach/exercises.html.twig` (ligne 309)

```twig
{# ✅ CORRECT #}
<a href="{{ path('coach_exercise_detail', {exerciseId: ex.id}) }}"
   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-800 hover:bg-white hover:text-black rounded-xl font-bold text-xs transition duration-300">
    View Details <i class="fas fa-arrow-right text-[10px]"></i>
</a>
```

### Changement:
```diff
- path('coach_exercise_detail', {id: ex.id})
+ path('coach_exercise_detail', {exerciseId: ex.id})
```

## 📋 Comprendre les Deux Routes

### 1. Route API (`coach_exercise_detail`)

**URL**: `/coach/api-exercises/{exerciseId}`

**Utilisation**: Exercices de l'API RapidAPI (Exercise Library)

**Exemple**:
```
/coach/api-exercises/0001
/coach/api-exercises/0123
```

**Contrôleur**:
```php
#[Route('/api-exercises/{exerciseId}', name: 'coach_exercise_detail')]
public function exerciseDetail(
    string $exerciseId,
    ExerciseApiService $service
): Response {
    $exercise = $service->getExerciseById($exerciseId);
    // ...
}
```

### 2. Route DB (`coach_exercise_detail_db`)

**URL**: `/coach/exercises/{id}`

**Utilisation**: Exercices créés par le coach (stockés en DB)

**Exemple**:
```
/coach/exercises/1
/coach/exercises/42
```

**Contrôleur**:
```php
#[Route('/exercises/{id}', name: 'coach_exercise_detail_db')]
public function exerciseDetail(Exercise $exercise): Response
{
    return $this->render('coach/exercise_detail_db.html.twig', [
        'exercise' => $exercise,
    ]);
}
```

## 🔄 Flux de Navigation

### Exercise Library (API RapidAPI)

```
1. Coach clique sur "Exercise Library"
   ↓
2. Affiche /coach/api-exercises
   ↓
3. Liste des exercices de l'API
   ↓
4. Coach clique sur "View Details"
   ↓
5. Redirige vers /coach/api-exercises/{exerciseId}
   ✅ Utilise coach_exercise_detail avec exerciseId
```

### Exercises Créés (Base de Données)

```
1. Coach clique sur "My Exercises"
   ↓
2. Affiche /coach/exercises
   ↓
3. Liste des exercices créés par le coach
   ↓
4. Coach clique sur un exercice
   ↓
5. Redirige vers /coach/exercises/{id}
   ✅ Utilise coach_exercise_detail_db avec id
```

## 🧪 Test

### Test Manuel:

1. Se connecter en tant que coach
2. Aller sur "Exercise Library" (`/coach/api-exercises`)
3. Cliquer sur "View Details" d'un exercice
4. Vérifier que la page de détails s'affiche correctement

### URLs Attendues:

```
✅ /coach/api-exercises/0001
✅ /coach/api-exercises/0123
✅ /coach/api-exercises/0456
```

## 📝 Bonnes Pratiques

### Nommage des Paramètres de Route

Pour éviter ce genre d'erreur à l'avenir:

1. **Soyez cohérent** dans le nommage des paramètres
2. **Documentez** les routes dans les contrôleurs
3. **Utilisez des noms explicites**:
   - `exerciseId` pour API externe
   - `id` pour entités Doctrine
   - `uuid` pour identifiants UUID

### Exemple de Documentation:

```php
/**
 * Affiche les détails d'un exercice de l'API RapidAPI
 * 
 * @param string $exerciseId ID de l'exercice dans l'API (ex: "0001")
 */
#[Route('/api-exercises/{exerciseId}', name: 'coach_exercise_detail')]
public function exerciseDetail(string $exerciseId): Response
{
    // ...
}
```

## ✅ Résultat

- ✅ Erreur corrigée
- ✅ Paramètre `exerciseId` correctement passé
- ✅ Navigation vers les détails fonctionne
- ✅ Exercise Library opérationnelle

## 📁 Fichiers Modifiés

1. **templates/coach/exercises.html.twig** (ligne 309)
   - Changement: `{id: ex.id}` → `{exerciseId: ex.id}`

---

**Date**: 4 Mars 2026
**Statut**: ✅ Corrigé
**Impact**: Exercise Library fonctionne maintenant
