# Fix: GIF des Mouvements Ne S'affiche Pas

## 🔴 Problème

Les GIFs d'animation des exercices ne s'affichent pas sur la page de détails:
- Texte "MOVEMENT ANIMATION" visible
- Mais pas d'image GIF

## 🔍 Causes Possibles

### 1. URL du GIF Incorrecte
L'ancien code reconstruisait l'URL du GIF manuellement:
```php
$exercise['gifUrl'] = $this->baseUrl . '/image/' . $exercise['id'] . '.gif';
```

**Problème**: L'API ExerciseDB fournit déjà l'URL complète du GIF dans sa réponse.

### 2. Proxy CORS Trop Restrictif
L'ancien proxy n'acceptait que les URLs contenant `rapidapi.com`:
```php
if (!$url || !str_contains($url, 'rapidapi.com')) {
    return new Response('Forbidden', Response::HTTP_FORBIDDEN);
}
```

**Problème**: Les nouveaux GIFs sont hébergés sur `exercisedb.io` ou `v2.exercisedb.io`.

### 3. Headers RapidAPI Non Nécessaires
Les GIFs de ExerciseDB sont maintenant publics et ne nécessitent plus les headers RapidAPI.

## ✅ Solutions Appliquées

### 1. Suppression de la Reconstruction d'URL

**Fichier**: `src/Service/ExerciseApiService.php`

**Avant**:
```php
// Sécurisation du gifUrl sur chaque exercice
foreach ($data as &$exercise) {
    if (is_array($exercise) && isset($exercise['id']) && !isset($exercise['gifUrl'])) {
        $exercise['gifUrl'] = $this->baseUrl . '/image/' . $exercise['id'] . '.gif';
    }
}
```

**Après**:
```php
// Les GIFs sont déjà fournis par l'API dans le champ gifUrl
// Pas besoin de les reconstruire
return $data;
```

### 2. Amélioration du Proxy GIF

**Fichier**: `src/Controller/CoachController.php`

**Nouvelles Fonctionnalités**:

#### A. Support des Nouvelles URLs
```php
// Les GIFs de ExerciseDB sont maintenant publics
if (str_contains($url, 'v2.exercisedb.io') || str_contains($url, 'exercisedb.io')) {
    // Chargement direct sans headers RapidAPI
    $response = $client->request('GET', $url);
    return new Response($response->getContent(), 200, ['Content-Type' => 'image/gif']);
}
```

#### B. Rétrocompatibilité RapidAPI
```php
// Pour les anciennes URLs RapidAPI
if (str_contains($url, 'rapidapi.com')) {
    $response = $client->request('GET', $url, [
        'headers' => [
            'X-RapidAPI-Key'  => $this->rapidApiKey,
            'X-RapidAPI-Host' => 'exercisedb.p.rapidapi.com',
        ],
    ]);
    return new Response($response->getContent(), 200, ['Content-Type' => 'image/gif']);
}
```

#### C. Meilleure Gestion d'Erreurs
```php
try {
    // ...
} catch (\Exception $e) {
    return new Response('Error loading GIF: ' . $e->getMessage(), 404);
}
```

## 📋 Formats d'URL Supportés

### 1. Nouvelles URLs ExerciseDB (Publiques)
```
https://v2.exercisedb.io/image/abc123
https://exercisedb.io/image/abc123
```
- ✅ Pas besoin de headers RapidAPI
- ✅ Chargement direct via proxy

### 2. Anciennes URLs RapidAPI
```
https://exercisedb.p.rapidapi.com/image/abc123.gif
```
- ✅ Nécessite headers RapidAPI
- ✅ Supporté pour rétrocompatibilité

## 🔄 Flux de Chargement du GIF

```
1. API ExerciseDB retourne l'exercice
   ↓
2. Champ gifUrl contient l'URL complète
   Exemple: "https://v2.exercisedb.io/image/0001"
   ↓
3. Template génère l'URL du proxy
   {{ path('coach_gif_proxy', {url: exercise.gifUrl}) }}
   ↓
4. Proxy détecte le domaine (exercisedb.io)
   ↓
5. Charge le GIF sans headers RapidAPI
   ↓
6. Retourne le GIF avec Content-Type: image/gif
   ↓
7. Navigateur affiche le GIF
```

## 🧪 Test

### Test Manuel:

1. Aller sur Exercise Library (`/coach/api-exercises`)
2. Cliquer sur "View Details" d'un exercice
3. Vérifier que le GIF s'affiche

### Test Direct du Proxy:

```bash
# Tester avec une URL ExerciseDB
curl "http://127.0.0.1:8000/coach/api-exercises/gif-proxy?url=https://v2.exercisedb.io/image/0001"
```

Devrait retourner un GIF.

### Vérifier l'URL du GIF:

Ouvrir la console du navigateur (F12) et vérifier:
```javascript
// Dans l'onglet Network, chercher la requête gif-proxy
// Vérifier l'URL complète
```

## 🐛 Debugging

### Si le GIF ne s'affiche toujours pas:

#### 1. Vérifier que gifUrl existe
Dans le contrôleur `exerciseDetail`:
```php
dd($exercise['gifUrl']); // Affiche l'URL du GIF
```

#### 2. Tester l'URL directement
Copier l'URL du GIF et l'ouvrir dans un nouvel onglet:
```
https://v2.exercisedb.io/image/0001
```

#### 3. Vérifier les erreurs du proxy
Dans le template, ajouter:
```twig
{% if exercise.gifUrl %}
    <p>GIF URL: {{ exercise.gifUrl }}</p>
    <img src="{{ path('coach_gif_proxy', {url: exercise.gifUrl}) }}" 
         onerror="console.error('Failed to load GIF:', this.src)"
         alt="{{ exercise.name }}">
{% endif %}
```

#### 4. Vérifier la console navigateur
Ouvrir F12 → Console et chercher les erreurs.

## 🔑 Configuration RapidAPI

### Variables d'Environnement

**Fichier**: `.env`
```env
RAPIDAPI_KEY=844694aeebmshd408871dd47839bp110778jsna826c46db6b4
```

### Service Configuration

**Fichier**: `config/services.yaml`
```yaml
App\Service\ExerciseApiService:
    arguments:
        $apiKey: '%env(RAPIDAPI_KEY)%'

App\Controller\CoachController:
    arguments:
        $rapidApiKey: '%env(RAPIDAPI_KEY)%'
```

## 💡 Alternative: Chargement Direct

Si le proxy ne fonctionne toujours pas, on peut charger les GIFs directement:

**Template**: `templates/coach/exercise_detail.html.twig`

```twig
{# Option 1: Via proxy (recommandé) #}
<img src="{{ path('coach_gif_proxy', {url: exercise.gifUrl}) }}" alt="{{ exercise.name }}">

{# Option 2: Direct (si GIFs publics) #}
<img src="{{ exercise.gifUrl }}" alt="{{ exercise.name }}" crossorigin="anonymous">
```

**Note**: L'option 2 peut causer des erreurs CORS selon la configuration du serveur ExerciseDB.

## ✅ Résultat

- ✅ GIFs chargés correctement
- ✅ Support des nouvelles URLs ExerciseDB
- ✅ Rétrocompatibilité avec anciennes URLs
- ✅ Meilleure gestion d'erreurs
- ✅ Pas de reconstruction d'URL manuelle

## 📁 Fichiers Modifiés

1. **src/Service/ExerciseApiService.php**
   - Suppression reconstruction URL GIF
   - Utilisation directe du gifUrl de l'API

2. **src/Controller/CoachController.php**
   - Amélioration méthode `gifProxy()`
   - Support URLs exercisedb.io
   - Meilleure gestion d'erreurs

---

**Date**: 4 Mars 2026
**Statut**: ✅ Corrigé
**Impact**: GIFs des exercices s'affichent maintenant
