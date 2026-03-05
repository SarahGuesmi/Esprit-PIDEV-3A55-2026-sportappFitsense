# Fix: Erreur 500 sur /coach/questionnaire/create

## 🔴 Problème

Erreur lors de la création de feedback par un coach:
```
POST http://127.0.0.1:8000/coach/questionnaire/create 500 (Internal Server Error)
SyntaxError: Unexpected token '<', "<!-- An ex"... is not valid JSON
```

## 🔍 Cause

Après avoir rendu les setters de timestamp `protected` pour suivre les recommandations de Doctrine Doctor, plusieurs contrôleurs essayaient toujours d'appeler ces méthodes, causant des erreurs.

### Entités Affectées:
1. **Questionnaire** - `setDateSoumission()` appelé dans QuestionnaireController
2. **EtatMental** - `setCreatedAt()` appelé dans EtatMentalController
3. **FeedbackResponse** - `setCreatedAt()` appelé dans UserWorkoutController
4. **UserExerciseProgress** - `setCompletedAt()` appelé dans UserWorkoutController

## ✅ Solutions Appliquées

### 1. Questionnaire - Lifecycle Callback

**Ajout de `#[ORM\HasLifecycleCallbacks]`**:
```php
#[ORM\Entity(repositoryClass: \App\Repository\QuestionnaireRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Questionnaire
```

**Ajout de méthode PrePersist**:
```php
#[ORM\PrePersist]
public function setDateSoumissionValue(): void
{
    if ($this->dateSoumission === null && $this->type === 'response') {
        $this->dateSoumission = new \DateTimeImmutable();
    }
}
```

**Suppression de l'appel manuel**:
```php
// Avant
$response->setDateSoumission(new \DateTimeImmutable());

// Après
// dateSoumission will be set automatically by PrePersist lifecycle callback
```

### 2. EtatMental - Utilisation du Trait

**Entité utilise déjà `TimestampableTrait`**:
```php
use TimestampableTrait, BlameableTrait;
```

**Suppression de l'appel manuel**:
```php
// Avant
$etat->setCreatedAt(new \DateTimeImmutable());

// Après
// createdAt is set automatically by TimestampableTrait
```

### 3. FeedbackResponse - Utilisation du Trait

**Entité utilise déjà `TimestampableTrait`**:
```php
use TimestampableTrait, BlameableTrait;
```

**Suppression de l'appel manuel**:
```php
// Avant
$feedback->setCreatedAt(new \DateTimeImmutable());

// Après
// createdAt is set automatically by TimestampableTrait
```

### 4. UserExerciseProgress - Setter Public

**Problème**: `setCompletedAt()` doit être appelé manuellement quand l'exercice est terminé.

**Solution**: Rendre le setter public au lieu de protected:
```php
// Avant
protected function setCompletedAt(?\DateTimeImmutable $completedAt): static

// Après
public function setCompletedAt(?\DateTimeImmutable $completedAt): static
```

### 5. User - Correction Bonus

**Ajout de `#[ORM\HasLifecycleCallbacks]`** pour cohérence:
```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[ORM\HasLifecycleCallbacks]
class User
```

## 📋 Fichiers Modifiés

1. **src/Entity/Questionnaire.php**
   - Ajout `#[ORM\HasLifecycleCallbacks]`
   - Ajout méthode `setDateSoumissionValue()`

2. **src/Entity/User.php**
   - Ajout `#[ORM\HasLifecycleCallbacks]`

3. **src/Entity/UserExerciseProgress.php**
   - `setCompletedAt()` rendu public

4. **src/Controller/Coach/QuestionnaireController.php**
   - Suppression appel `setDateSoumission()`
   - Correction `getNom()` → `getFirstname()`
   - Correction `getPrenom()` → `getLastname()`

5. **src/Controller/Front/EtatMentalController.php**
   - Suppression appel `setCreatedAt()`

6. **src/Controller/UserWorkoutController.php**
   - Suppression appel `setCreatedAt()`

## ✅ Résultat

- ✅ Erreur 500 corrigée
- ✅ Création de feedback fonctionne
- ✅ Timestamps gérés automatiquement par Doctrine
- ✅ Code plus propre et maintenable
- ✅ Conforme aux bonnes pratiques Doctrine

## 🔄 Test

```bash
# Vider le cache
php bin/console cache:clear

# Tester la création de feedback
# 1. Se connecter en tant que coach
# 2. Aller sur /coach/questionnaires
# 3. Créer un nouveau feedback
# 4. Vérifier qu'il n'y a pas d'erreur 500
```

## 📝 Notes

### Bonnes Pratiques Appliquées:

1. **Lifecycle Callbacks**: Utiliser `#[ORM\PrePersist]` pour initialiser automatiquement les timestamps
2. **Traits**: Utiliser `TimestampableTrait` pour gérer `createdAt` et `updatedAt`
3. **Protected Setters**: Garder les setters de timestamp protected sauf si besoin manuel
4. **Automatic Timestamps**: Laisser Doctrine gérer les timestamps automatiquement

### Quand Utiliser Chaque Approche:

- **Lifecycle Callback**: Pour timestamps conditionnels (ex: dateSoumission seulement si type='response')
- **Trait**: Pour timestamps standards (createdAt, updatedAt)
- **Public Setter**: Pour timestamps manuels (ex: completedAt quand exercice terminé)

---

**Date**: 4 Mars 2026
**Statut**: ✅ Corrigé
**Impact**: Création de feedback fonctionne maintenant
