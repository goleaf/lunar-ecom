# Size Guides & Fit Finder System

This document describes the interactive size guides and fit finder system implemented for the admin panel to help reduce returns.

## Overview

The system consists of three main components:
1. **Size Guides** - Measurement charts with size information
2. **Fit Finder Quizzes** - Interactive quizzes to help customers find their perfect fit
3. **Fit Feedback** - Customer feedback system to track fit issues and reduce returns

## Database Structure

### Tables Created

1. **size_guides** - Stores size guide information with measurement charts
2. **fit_finder_quizzes** - Stores fit finder quiz configurations
3. **fit_finder_questions** - Questions within quizzes
4. **fit_finder_answers** - Answer options for questions
5. **fit_feedbacks** - Customer feedback about product fit
6. **product_size_guides** - Pivot table linking products to size guides
7. **product_fit_finder_quizzes** - Pivot table linking products to fit finder quizzes

## Models

### SizeGuide
- **Location**: `app/Models/SizeGuide.php`
- **Features**:
  - Name, description, category type, gender
  - Measurement unit (cm, inches, both)
  - Size chart data (JSON format)
  - Active/inactive status
  - Display order
  - Relationships: products (many-to-many), fitFeedbacks (has-many)

### FitFinderQuiz
- **Location**: `app/Models/FitFinderQuiz.php`
- **Features**:
  - Name, description, category type, gender
  - Associated size guide
  - Recommendation logic (JSON format for size recommendation rules)
  - Active/inactive status
  - Display order
  - Relationships: sizeGuide (belongs-to), questions (has-many), products (many-to-many), fitFeedbacks (has-many)

### FitFinderQuestion
- **Location**: `app/Models/FitFinderQuestion.php`
- **Features**:
  - Question text and type (single_choice, multiple_choice, text, number)
  - Display order
  - Required/optional flag
  - Help text
  - Relationships: quiz (belongs-to), answers (has-many)

### FitFinderAnswer
- **Location**: `app/Models/FitFinderAnswer.php`
- **Features**:
  - Answer text and value
  - Display order
  - Size adjustment (JSON format)
  - Relationships: question (belongs-to)

### FitFeedback
- **Location**: `app/Models/FitFeedback.php`
- **Features**:
  - Product, customer, order information
  - Purchased size vs recommended size
  - Actual fit (perfect, too_small, too_large, too_tight, too_loose)
  - Fit rating (1-5)
  - Body measurements (JSON)
  - Feedback text
  - Would exchange/return flags
  - Helpful/public flags for moderation
  - Relationships: product, sizeGuide, fitFinderQuiz, customer (all belong-to)

## Admin Interface (Filament)

### Size Guide Resource
- **Location**: `app/Filament/Resources/SizeGuideResource.php`
- **Features**:
  - Create, edit, delete size guides
  - Manage size chart data (JSON format)
  - Associate products
  - Filter by category type, gender, active status
  - Sort by display order

### Fit Finder Quiz Resource
- **Location**: `app/Filament/Resources/FitFinderQuizResource.php`
- **Features**:
  - Create, edit, delete quizzes
  - Manage recommendation logic (JSON format)
  - Associate size guides and products
  - Manage questions via relation manager
  - Filter by category type, gender, active status

### Fit Finder Question Resource
- **Location**: `app/Filament/Resources/FitFinderQuestionResource.php`
- **Features**:
  - Create, edit, delete questions
  - Manage answers via relation manager
  - Filter by quiz, question type

### Fit Feedback Resource
- **Location**: `app/Filament/Resources/FitFeedbackResource.php`
- **Features**:
  - View and manage customer feedback
  - Filter by fit rating, actual fit, return status
  - Mark feedback as helpful or public
  - Bulk actions to mark multiple feedbacks as helpful
  - Statistics on fit issues

## Product Model Extensions

The Product model has been extended with:
- `sizeGuides()` - Many-to-many relationship with size guides
- `fitFinderQuizzes()` - Many-to-many relationship with fit finder quizzes
- `fitFeedbacks()` - Has-many relationship with fit feedback

## Usage Examples

### Creating a Size Guide

1. Navigate to **Product Management > Size Guides** in admin panel
2. Click **Create Size Guide**
3. Fill in:
   - Name: "Men's T-Shirt Size Guide"
   - Category Type: "Clothing"
   - Gender: "Men"
   - Measurement Unit: "cm"
   - Size Chart Data (JSON):
   ```json
   {
     "sizes": [
       {
         "size": "S",
         "chest": "86-91",
         "waist": "71-76",
         "hips": "91-96"
       },
       {
         "size": "M",
         "chest": "91-96",
         "waist": "76-81",
         "hips": "96-101"
       }
     ]
   }
   ```
4. Associate products that should use this size guide

### Creating a Fit Finder Quiz

1. Navigate to **Product Management > Fit Finder Quizzes**
2. Click **Create Fit Finder Quiz**
3. Fill in basic information
4. Add questions using the Questions relation manager
5. For each question, add answer options
6. Configure recommendation logic (JSON format):
   ```json
   [
     {
       "conditions": [
         {"question_id": 1, "answer_id": 2}
       ],
       "recommended_size": "M"
     }
   ]
   ```

### Viewing Fit Feedback

1. Navigate to **Product Management > Fit Feedbacks**
2. View customer feedback about product fit
3. Filter by:
   - Actual fit (perfect, too_small, etc.)
   - Fit rating (1-5)
   - Return status
4. Mark helpful feedback as public to show to other customers
5. Use statistics to identify common fit issues

## Size Chart Data Format

The `size_chart_data` field uses JSON format:

```json
{
  "sizes": [
    {
      "size": "S",
      "measurements": {
        "chest": "86-91",
        "waist": "71-76",
        "hips": "91-96",
        "length": "70"
      }
    }
  ],
  "notes": "Measurements in cm. Please refer to our measuring guide."
}
```

## Recommendation Logic Format

The `recommendation_logic` field uses JSON format:

```json
[
  {
    "conditions": [
      {"question_id": 1, "answer_id": 2},
      {"question_id": 2, "answer_id": 5}
    ],
    "recommended_size": "M",
    "confidence": "high"
  }
]
```

## Benefits

1. **Reduced Returns**: Help customers choose the right size before purchase
2. **Data Collection**: Gather fit feedback to improve size recommendations
3. **Customer Confidence**: Interactive tools increase customer confidence
4. **Analytics**: Track fit issues to identify problematic products or sizes
5. **Moderation**: Admin can review and moderate feedback before making it public

## Next Steps

To use this system on the storefront:

1. Create API endpoints or Livewire components to display size guides
2. Build interactive fit finder quiz component
3. Create feedback form for customers to submit fit feedback
4. Display public fit feedback on product pages
5. Integrate with order system to automatically request feedback after delivery

## Migration

Run the migrations:

```bash
php artisan migrate
```

All migrations are prefixed with `2025_12_25_` and include:
- `create_size_guides_table.php`
- `create_fit_finder_quizzes_table.php`
- `create_fit_finder_questions_table.php`
- `create_fit_finder_answers_table.php`
- `create_fit_feedbacks_table.php`
- `create_product_size_guides_pivot_table.php`
- `create_product_fit_finder_quizzes_pivot_table.php`

