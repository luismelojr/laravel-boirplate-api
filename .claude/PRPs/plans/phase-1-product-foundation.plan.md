# Plan: Fase 1 — Fundação do Produto

## Summary

Implementar o módulo central de produtos do ERP fitness: cadastro de produtos com grade de variações (tamanho × cor × modelo), organização por categorias/subcategorias, marcas e coleções, fotos por variação via Media Library, e controle de estoque inicial por variação. Esta fase é o alicerce de todo o sistema — sem ela, PDV, estoque e catálogo não funcionam.

## User Story

Como dono da loja fitness,
Quero cadastrar produtos com todas as variações (tamanho, cor, modelo) e definir preço, custo e estoque por variação,
Para ter controle real do que tenho em estoque e calcular minha margem antes de qualquer venda.

## Problem → Solution

Não existe cadastro de produto → Sistema com Product + ProductVariant onde cada combinação tamanho×cor tem SKU, estoque e preço próprios.

## Metadata
- **Complexity**: XL
- **Source PRD**: `.claude/PRPs/prds/erp-fitness-store.prd.md`
- **PRD Phase**: Fase 1 — Fundação do Produto
- **Estimated Files**: ~45 arquivos novos, 1 atualizado (routes/api.php)

---

## UX Design

### Before
N/A — sistema novo, não existe produto cadastrado.

### After

```
GET  /api/v1/products               → lista paginada com filtros
POST /api/v1/products               → cria produto
GET  /api/v1/products/{uuid}        → produto com variações, fotos, relações
PUT  /api/v1/products/{uuid}        → atualiza produto
DELETE /api/v1/products/{uuid}      → soft delete
POST /api/v1/products/{uuid}/duplicate → duplica em draft com stock=0
POST /api/v1/products/{uuid}/media  → upload de foto
DELETE /api/v1/products/{uuid}/media/{uuid} → remove foto

GET  /api/v1/products/{uuid}/variants   → variações do produto
POST /api/v1/products/{uuid}/variants   → cria variação (SKU auto-gerado)
GET  /api/v1/variants/{uuid}            → variação individual (shallow)
PUT  /api/v1/variants/{uuid}            → atualiza variação
DELETE /api/v1/variants/{uuid}          → remove variação

GET  /api/v1/categories    → árvore de categorias
POST /api/v1/categories    → cria categoria ou subcategoria
GET  /api/v1/brands        → lista de marcas
GET  /api/v1/collections   → lista de coleções
```

### Interaction Changes

| Endpoint | Antes | Depois | Notas |
|----------|-------|--------|-------|
| `GET /api/v1/products` | 404 | 200 com lista paginada | Filtros: status, gender, product_type |
| `POST /api/v1/products` | 404 | 201 com produto criado | Slug gerado automaticamente |
| `POST /api/v1/products/{uuid}/variants` | 404 | 201 + SKU automático | SKU = `{sku_produto}-{size}-{color}` |
| `GET /api/v1/categories` | 404 | 200 com categorias + subcategorias | |

---

## Mandatory Reading

| Prioridade | Arquivo | Por quê |
|-----------|---------|---------|
| P0 | `app/Domain/Auth/Services/RegisterTenantService.php` | Padrão canônico de Service |
| P0 | `app/Domain/Auth/Data/RegisterData.php` | Padrão canônico de DTO |
| P0 | `app/Http/Controllers/Api/V1/Dashboard/AuthController.php` | Padrão canônico de Controller |
| P0 | `app/Http/Requests/Api/V1/Dashboard/Auth/RegisterRequest.php` | Padrão de FormRequest |
| P0 | `app/Http/Resources/Api/V1/Dashboard/User/UserResource.php` | Padrão de Resource |
| P0 | `app/Models/User.php` | Padrão de Model completo |
| P0 | `app/Traits/BelongsToTenant.php` | Como funciona o escopo de tenant |
| P0 | `app/Traits/HasUuid.php` | Auto-geração de UUID |
| P1 | `app/Traits/ApiResponse.php` | Todos os métodos de resposta |
| P1 | `app/Enums/UserStatusEnum.php` | Padrão de Enum |
| P1 | `tests/Feature/TenantRegistrationTest.php` | Padrão de teste de feature |
| P1 | `tests/Pest.php` | Helper tenantActingAs() |
| P1 | `database/migrations/2026_06_18_153051_create_user_invitations_table.php` | Migration com tenant_id FK |
| P1 | `routes/api.php` | Estrutura de rotas existente |
| P2 | `database/factories/UserFactory.php` | Padrão de Factory com estados |

---

## External Documentation

| Tópico | Takeaway chave |
|--------|----------------|
| Spatie Media Library | `addMediaCollection()` no model; upload via `addMedia($file)->toMediaCollection('photos')` |
| Spatie Laravel Data | `Data::from($request->validated())`; sem `from()` manual; `#[Hidden]` para campos sensíveis |
| Spatie Query Builder | `QueryBuilder::for(Product::class)->allowedFilters()->allowedIncludes()->paginate()` |

---

## Patterns to Mirror

### SERVICE_PATTERN
```php
// SOURCE: app/Domain/Auth/Services/RegisterTenantService.php
declare(strict_types=1);

class CreateProductService
{
    public function handle(CreateProductData $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([...]);
            // side effects FORA da transação (notificações, emails)
            return $product->fresh();
        }, 3); // 3 retries em deadlock
    }
}
```

### DTO_PATTERN
```php
// SOURCE: app/Domain/Auth/Data/RegisterData.php
declare(strict_types=1);

use Spatie\LaravelData\Data;

class CreateProductData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,
        // sem from() manual — herdado de Data
    ) {}
}
// Uso: CreateProductData::from($request->validated())
```

### CONTROLLER_PATTERN
```php
// SOURCE: app/Http/Controllers/Api/V1/Dashboard/AuthController.php
public function store(StoreProductRequest $request, CreateProductService $service): JsonResponse
{
    $data = CreateProductData::from($request->validated());
    $product = $service->handle($data);

    return $this->created(new ProductResource($product), 'Produto criado com sucesso');
}
```

### FORM_REQUEST_PATTERN
```php
// SOURCE: app/Http/Requests/Api/V1/Dashboard/Auth/RegisterRequest.php
public function authorize(): bool { return true; }

public function rules(): array { return [...]; }

public function messages(): array {
    return [
        'name.required' => 'O nome é obrigatório.',     // PT-BR sempre
        'name.max' => 'O nome não pode ter mais de :max caracteres.',
    ];
}
```

### RESOURCE_PATTERN
```php
// SOURCE: app/Http/Resources/Api/V1/Dashboard/User/UserResource.php
public function toArray(Request $request): array
{
    return [
        'uuid' => $this->uuid,            // nunca expor 'id'
        'name' => $this->name,
        'status' => $this->status->value, // enum → string via ->value
        'created_at' => $this->created_at?->format('d/m/Y H:i:s'),
    ];
}
```

### MODEL_PATTERN
```php
// SOURCE: app/Models/User.php
declare(strict_types=1);

class Product extends Model implements Auditable, HasMedia
{
    use BelongsToTenant, HasFactory, HasUuid, SoftDeletes;
    use InteractsWithMedia, \OwenIt\Auditing\Auditable;

    protected $hidden = ['id', 'tenant_id', 'deleted_at']; // sempre ocultar

    protected function casts(): array   // MÉTODO, não propriedade $casts
    {
        return ['status' => ProductStatusEnum::class];
    }

    public function getRouteKeyName(): string { return 'uuid'; }
}
```

### ENUM_PATTERN
```php
// SOURCE: app/Enums/UserStatusEnum.php
declare(strict_types=1);

enum ProductStatusEnum: string
{
    case Active = 'active';
    case Draft = 'draft';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### MIGRATION_PATTERN
```php
// SOURCE: database/migrations/2026_06_18_153051_create_user_invitations_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void { Schema::dropIfExists('products'); }
};
```

### TEST_STRUCTURE
```php
// SOURCE: tests/Feature/TenantRegistrationTest.php
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
    $this->user = User::factory()->create();
    tenantActingAs($this->user);
});

it('creates a product', function () {
    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/products', [...]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Legging Preta');
});
```

---

## Files to Change

### Migrations (criar em ordem — dependencies importam)

| Arquivo | Ação |
|---------|------|
| `database/migrations/YYYY_MM_DD_001_create_categories_table.php` | CRIAR |
| `database/migrations/YYYY_MM_DD_002_create_brands_table.php` | CRIAR |
| `database/migrations/YYYY_MM_DD_003_create_collections_table.php` | CRIAR |
| `database/migrations/YYYY_MM_DD_004_create_products_table.php` | CRIAR |
| `database/migrations/YYYY_MM_DD_005_create_product_variants_table.php` | CRIAR |

### Enums

| Arquivo | Ação |
|---------|------|
| `app/Enums/ProductStatusEnum.php` | CRIAR |
| `app/Enums/ProductGenderEnum.php` | CRIAR |

### Models

| Arquivo | Ação |
|---------|------|
| `app/Models/Category.php` | CRIAR |
| `app/Models/Brand.php` | CRIAR |
| `app/Models/Collection.php` | CRIAR |
| `app/Models/Product.php` | CRIAR |
| `app/Models/ProductVariant.php` | CRIAR |

### Domain — DTOs

| Arquivo | Ação |
|---------|------|
| `app/Domain/Product/Data/CreateCategoryData.php` | CRIAR |
| `app/Domain/Product/Data/UpdateCategoryData.php` | CRIAR |
| `app/Domain/Product/Data/CreateBrandData.php` | CRIAR |
| `app/Domain/Product/Data/UpdateBrandData.php` | CRIAR |
| `app/Domain/Product/Data/CreateCollectionData.php` | CRIAR |
| `app/Domain/Product/Data/UpdateCollectionData.php` | CRIAR |
| `app/Domain/Product/Data/CreateProductData.php` | CRIAR |
| `app/Domain/Product/Data/UpdateProductData.php` | CRIAR |
| `app/Domain/Product/Data/CreateProductVariantData.php` | CRIAR |
| `app/Domain/Product/Data/UpdateProductVariantData.php` | CRIAR |

### Domain — Services

| Arquivo | Ação |
|---------|------|
| `app/Domain/Product/Services/CreateCategoryService.php` | CRIAR |
| `app/Domain/Product/Services/UpdateCategoryService.php` | CRIAR |
| `app/Domain/Product/Services/DeleteCategoryService.php` | CRIAR |
| `app/Domain/Product/Services/CreateBrandService.php` | CRIAR |
| `app/Domain/Product/Services/UpdateBrandService.php` | CRIAR |
| `app/Domain/Product/Services/DeleteBrandService.php` | CRIAR |
| `app/Domain/Product/Services/CreateCollectionService.php` | CRIAR |
| `app/Domain/Product/Services/UpdateCollectionService.php` | CRIAR |
| `app/Domain/Product/Services/DeleteCollectionService.php` | CRIAR |
| `app/Domain/Product/Services/CreateProductService.php` | CRIAR |
| `app/Domain/Product/Services/UpdateProductService.php` | CRIAR |
| `app/Domain/Product/Services/DeleteProductService.php` | CRIAR |
| `app/Domain/Product/Services/DuplicateProductService.php` | CRIAR |
| `app/Domain/Product/Services/CreateProductVariantService.php` | CRIAR |
| `app/Domain/Product/Services/UpdateProductVariantService.php` | CRIAR |
| `app/Domain/Product/Services/DeleteProductVariantService.php` | CRIAR |
| `app/Domain/Product/Services/UploadProductMediaService.php` | CRIAR |

### HTTP — Form Requests

| Arquivo | Ação |
|---------|------|
| `app/Http/Requests/Api/V1/Dashboard/Category/StoreCategoryRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/Category/UpdateCategoryRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/Brand/StoreBrandRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/Brand/UpdateBrandRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/Collection/StoreCollectionRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/Collection/UpdateCollectionRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/Product/StoreProductRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/Product/UpdateProductRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/Product/UploadProductMediaRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/ProductVariant/StoreProductVariantRequest.php` | CRIAR |
| `app/Http/Requests/Api/V1/Dashboard/ProductVariant/UpdateProductVariantRequest.php` | CRIAR |

### HTTP — Resources

| Arquivo | Ação |
|---------|------|
| `app/Http/Resources/Api/V1/Dashboard/Category/CategoryResource.php` | CRIAR |
| `app/Http/Resources/Api/V1/Dashboard/Brand/BrandResource.php` | CRIAR |
| `app/Http/Resources/Api/V1/Dashboard/Collection/CollectionResource.php` | CRIAR |
| `app/Http/Resources/Api/V1/Dashboard/Product/ProductResource.php` | CRIAR |
| `app/Http/Resources/Api/V1/Dashboard/Product/ProductVariantResource.php` | CRIAR |

### HTTP — Controllers

| Arquivo | Ação |
|---------|------|
| `app/Http/Controllers/Api/V1/Dashboard/CategoryController.php` | CRIAR |
| `app/Http/Controllers/Api/V1/Dashboard/BrandController.php` | CRIAR |
| `app/Http/Controllers/Api/V1/Dashboard/CollectionController.php` | CRIAR |
| `app/Http/Controllers/Api/V1/Dashboard/ProductController.php` | CRIAR |
| `app/Http/Controllers/Api/V1/Dashboard/ProductVariantController.php` | CRIAR |

### Database — Factories

| Arquivo | Ação |
|---------|------|
| `database/factories/CategoryFactory.php` | CRIAR |
| `database/factories/BrandFactory.php` | CRIAR |
| `database/factories/CollectionFactory.php` | CRIAR |
| `database/factories/ProductFactory.php` | CRIAR |
| `database/factories/ProductVariantFactory.php` | CRIAR |

### Routes e Tests

| Arquivo | Ação |
|---------|------|
| `routes/api.php` | ATUALIZAR — adicionar rotas no grupo `auth:sanctum` |
| `tests/Feature/CategoryTest.php` | CRIAR |
| `tests/Feature/BrandTest.php` | CRIAR |
| `tests/Feature/CollectionTest.php` | CRIAR |
| `tests/Feature/ProductTest.php` | CRIAR |
| `tests/Feature/ProductVariantTest.php` | CRIAR |

## NOT Building

- Controle de movimentações de estoque (Phase 3)
- Pedido de compra e custo médio (Phase 2)
- `supplier_id` no produto (Phase 2 adiciona via migration)
- Promoções e cupons (Phase 6)
- Filtros de catálogo público (Phase 7)
- Comissão por produto (Phase 9)

---

## Step-by-Step Tasks

### Task 1: Criar Enums

- **ACTION**: Criar `ProductStatusEnum` e `ProductGenderEnum`
- **IMPLEMENT**:

```php
// app/Enums/ProductStatusEnum.php
<?php
declare(strict_types=1);
namespace App\Enums;

enum ProductStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Draft = 'draft';
    case OutOfStock = 'out_of_stock';
    case Discontinued = 'discontinued';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

```php
// app/Enums/ProductGenderEnum.php
<?php
declare(strict_types=1);
namespace App\Enums;

enum ProductGenderEnum: string
{
    case Masculine = 'masculine';
    case Feminine = 'feminine';
    case Unisex = 'unisex';
    case Children = 'children';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- **MIRROR**: ENUM_PATTERN
- **VALIDATE**: `vendor/bin/sail artisan tinker --execute 'echo App\Enums\ProductStatusEnum::Active->value;'` → `active`

---

### Task 2: Criar Migrations

- **ACTION**: Criar 5 migrations via artisan, depois editar o conteúdo
- **IMPLEMENT**: Executar na ordem correta:

```bash
vendor/bin/sail artisan make:migration create_categories_table --no-interaction
vendor/bin/sail artisan make:migration create_brands_table --no-interaction
vendor/bin/sail artisan make:migration create_collections_table --no-interaction
vendor/bin/sail artisan make:migration create_products_table --no-interaction
vendor/bin/sail artisan make:migration create_product_variants_table --no-interaction
```

**Schema de `categories`** (suporta subcategorias via self-referencing):
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
    $table->string('name');
    $table->string('slug');
    $table->text('description')->nullable();
    $table->unsignedInteger('position')->default(0);
    $table->boolean('is_visible')->default(true);
    $table->timestamps();
    $table->softDeletes();
    $table->unique(['tenant_id', 'slug']);
    $table->index(['tenant_id', 'parent_id']);
});
```

**Schema de `brands`**:
```php
Schema::create('brands', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('slug');
    $table->text('description')->nullable();
    $table->boolean('is_visible')->default(true);
    $table->timestamps();
    $table->softDeletes();
    $table->unique(['tenant_id', 'slug']);
});
```

**Schema de `collections`**:
```php
Schema::create('collections', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('slug');
    $table->text('description')->nullable();
    $table->boolean('is_visible')->default(true);
    $table->date('starts_at')->nullable();
    $table->date('ends_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->unique(['tenant_id', 'slug']);
});
```

**Schema de `products`**:
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->string('slug');
    $table->string('short_description')->nullable();
    $table->text('full_description')->nullable();
    $table->string('gender')->nullable();
    $table->string('product_type')->nullable();
    $table->string('unit')->default('un');
    $table->decimal('base_cost', 10, 2)->default(0);
    $table->decimal('base_price', 10, 2)->default(0);
    $table->decimal('promotional_price', 10, 2)->nullable();
    $table->decimal('desired_margin', 5, 2)->nullable();
    $table->decimal('markup', 5, 2)->nullable();
    $table->decimal('commission', 5, 2)->nullable();
    $table->decimal('weight', 8, 3)->nullable();
    $table->decimal('length', 8, 2)->nullable();
    $table->decimal('width', 8, 2)->nullable();
    $table->decimal('height', 8, 2)->nullable();
    $table->text('composition')->nullable();
    $table->text('care_instructions')->nullable();
    $table->string('internal_code')->nullable();
    $table->string('sku')->nullable();
    $table->string('barcode')->nullable();
    $table->string('status')->default('draft');
    $table->boolean('is_visible_in_catalog')->default(false);
    $table->boolean('is_available_in_pdv')->default(true);
    $table->boolean('allows_discount')->default(true);
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_new')->default(false);
    $table->integer('min_stock')->default(0);
    $table->integer('max_stock')->nullable();
    $table->integer('reorder_point')->default(0);
    $table->string('storage_location')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->unique(['tenant_id', 'sku']);
    $table->unique(['tenant_id', 'slug']);
    $table->index(['tenant_id', 'status']);
    $table->index(['tenant_id', 'category_id']);
});
```

**Schema de `product_variants`**:
```php
Schema::create('product_variants', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->string('size')->nullable();
    $table->string('color')->nullable();
    $table->string('model')->nullable();
    $table->string('pattern')->nullable();
    $table->string('fabric')->nullable();
    $table->string('gender')->nullable();
    $table->string('sku')->unique();
    $table->string('barcode')->nullable();
    $table->decimal('cost', 10, 2)->nullable();
    $table->decimal('price', 10, 2)->nullable();
    $table->integer('stock_quantity')->default(0);
    $table->integer('reserved_quantity')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    $table->index(['product_id', 'is_active']);
    $table->index(['tenant_id', 'sku']);
});
```

- **MIRROR**: MIGRATION_PATTERN
- **GOTCHA**: A ordem das migrations importa por causa das FKs. `product_variants` depende de `products`, que depende de `categories`, `brands`, `collections`. O `sku` em `product_variants` é `unique()` global (não por tenant) — cada variante deve ter SKU 100% único no sistema.
- **VALIDATE**: `vendor/bin/sail artisan migrate --no-interaction` sem erros

---

### Task 3: Criar Models

- **ACTION**: Criar 5 models seguindo exatamente o padrão de `User.php`
- **IMPLEMENT**:

**`app/Models/Category.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'tenant_id', 'parent_id', 'name', 'slug',
        'description', 'position', 'is_visible',
    ];

    protected $hidden = ['id', 'tenant_id', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
```

**`app/Models/Brand.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<BrandFactory> */
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'description', 'is_visible'];

    protected $hidden = ['id', 'tenant_id', 'deleted_at'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function getRouteKeyName(): string { return 'uuid'; }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
```

**`app/Models/Collection.php`** (similar a Brand, adicionar `starts_at` e `ends_at` como `date`):
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<CollectionFactory> */
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'tenant_id', 'name', 'slug', 'description',
        'is_visible', 'starts_at', 'ends_at',
    ];

    protected $hidden = ['id', 'tenant_id', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function getRouteKeyName(): string { return 'uuid'; }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
```

**`app/Models/Product.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductGenderEnum;
use App\Enums\ProductStatusEnum;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements Auditable, HasMedia
{
    use BelongsToTenant;
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'tenant_id', 'category_id', 'brand_id', 'collection_id',
        'name', 'slug', 'short_description', 'full_description',
        'gender', 'product_type', 'unit',
        'base_cost', 'base_price', 'promotional_price',
        'desired_margin', 'markup', 'commission',
        'weight', 'length', 'width', 'height',
        'composition', 'care_instructions',
        'internal_code', 'sku', 'barcode',
        'status', 'is_visible_in_catalog', 'is_available_in_pdv',
        'allows_discount', 'is_featured', 'is_new',
        'min_stock', 'max_stock', 'reorder_point', 'storage_location',
    ];

    protected $hidden = ['id', 'tenant_id', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ProductStatusEnum::class,
            'gender' => ProductGenderEnum::class,
            'is_visible_in_catalog' => 'boolean',
            'is_available_in_pdv' => 'boolean',
            'allows_discount' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'base_cost' => 'decimal:2',
            'base_price' => 'decimal:2',
            'promotional_price' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string { return 'uuid'; }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function collection(): BelongsTo { return $this->belongsTo(Collection::class); }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
        $this->addMediaConversion('catalog')->width(800)->height(800)->nonQueued();
    }
}
```

**`app/Models/ProductVariant.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductGenderEnum;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductVariant extends Model implements HasMedia
{
    use BelongsToTenant;
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'tenant_id', 'product_id',
        'size', 'color', 'model', 'pattern', 'fabric', 'gender',
        'sku', 'barcode', 'cost', 'price',
        'stock_quantity', 'reserved_quantity', 'is_active',
    ];

    protected $hidden = ['id', 'tenant_id', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'gender' => ProductGenderEnum::class,
            'is_active' => 'boolean',
            'stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string { return 'uuid'; }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
    }

    public function getEffectivePriceAttribute(): string
    {
        return $this->price ?? $this->product->base_price;
    }

    public function getEffectiveCostAttribute(): string
    {
        return $this->cost ?? $this->product->base_cost;
    }
}
```

- **MIRROR**: MODEL_PATTERN
- **GOTCHA**: `\OwenIt\Auditing\Auditable` precisa do namespace completo no `use` por conflito de nome. Media Library: `implements HasMedia` + `use InteractsWithMedia` + `registerMediaCollections()` + `registerMediaConversions()`. Nunca usar `$casts` propriedade — sempre método `casts()`.

---

### Task 4: Criar Factories

- **ACTION**: Criar factories para os 5 models
- **IMPLEMENT**:

```bash
vendor/bin/sail artisan make:factory CategoryFactory --model=Category --no-interaction
vendor/bin/sail artisan make:factory BrandFactory --model=Brand --no-interaction
vendor/bin/sail artisan make:factory CollectionFactory --model=Collection --no-interaction
vendor/bin/sail artisan make:factory ProductFactory --model=Product --no-interaction
vendor/bin/sail artisan make:factory ProductVariantFactory --model=ProductVariant --no-interaction
```

**`database/factories/ProductFactory.php`**:
```php
<?php

namespace Database\Factories;

use App\Enums\ProductStatusEnum;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'short_description' => fake()->sentence(),
            'product_type' => fake()->randomElement(['camiseta', 'legging', 'bermuda', 'top', 'short']),
            'base_cost' => fake()->randomFloat(2, 20, 100),
            'base_price' => fake()->randomFloat(2, 50, 300),
            'status' => ProductStatusEnum::Active->value,
            'is_available_in_pdv' => true,
            'is_visible_in_catalog' => true,
            'allows_discount' => true,
            'min_stock' => 2,
            'reorder_point' => 5,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatusEnum::Inactive->value,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatusEnum::Draft->value,
        ]);
    }

    public function discontinued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatusEnum::Discontinued->value,
        ]);
    }
}
```

**`database/factories/ProductVariantFactory.php`**:
```php
<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size' => fake()->randomElement(['PP', 'P', 'M', 'G', 'GG', 'XGG']),
            'color' => fake()->safeColorName(),
            'sku' => strtoupper(Str::random(4)).'-'.strtoupper(Str::random(6)),
            'stock_quantity' => fake()->numberBetween(0, 50),
            'reserved_quantity' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock_quantity' => 0]);
    }
}
```

- **MIRROR**: Padrão de `UserFactory.php`
- **GOTCHA**: O `slug` no `ProductFactory` precisa de `Str::random()` no sufixo para evitar colisão entre factories em testes com múltiplos produtos.

---

### Task 5: Criar DTOs

- **ACTION**: Criar todos os DTOs do domínio Product
- **IMPLEMENT**:

**`app/Domain/Product/Data/CreateProductData.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Product\Data;

use Spatie\LaravelData\Data;

class CreateProductData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly ?string $short_description,
        public readonly ?string $full_description,
        public readonly ?string $gender,
        public readonly ?string $product_type,
        public readonly ?int $category_id,
        public readonly ?int $brand_id,
        public readonly ?int $collection_id,
        public readonly float $base_cost,
        public readonly float $base_price,
        public readonly ?float $promotional_price,
        public readonly ?float $desired_margin,
        public readonly ?string $sku,
        public readonly ?string $barcode,
        public readonly ?string $internal_code,
        public readonly bool $is_visible_in_catalog,
        public readonly bool $is_available_in_pdv,
        public readonly bool $allows_discount,
        public readonly int $min_stock,
        public readonly ?int $max_stock,
        public readonly int $reorder_point,
        public readonly ?string $storage_location,
        public readonly ?string $composition,
        public readonly ?string $care_instructions,
        public readonly ?string $unit,
        public readonly ?float $weight,
        public readonly ?float $commission,
    ) {}
}
```

**`app/Domain/Product/Data/CreateProductVariantData.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Product\Data;

use Spatie\LaravelData\Data;

class CreateProductVariantData extends Data
{
    public function __construct(
        public readonly ?string $size,
        public readonly ?string $color,
        public readonly ?string $model,
        public readonly ?string $pattern,
        public readonly ?string $fabric,
        public readonly ?string $gender,
        public readonly ?string $barcode,
        public readonly ?float $cost,
        public readonly ?float $price,
        public readonly bool $is_active,
    ) {}
}
```

DTOs para Category, Brand, Collection seguem o mesmo padrão com seus campos respectivos.

- **MIRROR**: DTO_PATTERN
- **GOTCHA**: Sem `from()` manual. Nullable com `?` obrigatório para campos opcionais. `bool` sem nullable para booleanos com default.

---

### Task 6: Criar Services

- **ACTION**: Criar todos os services do domínio Product
- **IMPLEMENT**:

**`app/Domain/Product/Services/CreateProductService.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Product\Services;

use App\Domain\Product\Data\CreateProductData;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProductService
{
    public function handle(CreateProductData $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                ...$data->toArray(),
                'slug' => $this->generateSlug($data->name),
            ]);

            return $product->fresh();
        }, 3);
    }

    private function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $count = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
}
```

**`app/Domain/Product/Services/CreateProductVariantService.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Product\Services;

use App\Domain\Product\Data\CreateProductVariantData;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProductVariantService
{
    public function handle(Product $product, CreateProductVariantData $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data) {
            $variant = ProductVariant::create([
                ...$data->toArray(),
                'product_id' => $product->id,
                'sku' => $this->generateSku($product, $data),
            ]);

            return $variant->fresh();
        }, 3);
    }

    private function generateSku(Product $product, CreateProductVariantData $data): string
    {
        $parts = array_filter([
            $product->sku ?? strtoupper(Str::random(4)),
            $data->size ? strtoupper($data->size) : null,
            $data->color ? strtoupper(substr($data->color, 0, 3)) : null,
        ]);

        $base = implode('-', $parts);
        $sku = $base;
        $count = 1;

        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = "{$base}-{$count}";
            $count++;
        }

        return $sku;
    }
}
```

**`app/Domain/Product/Services/DuplicateProductService.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Product\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateProductService
{
    public function handle(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $duplicate = $product->replicate(['sku', 'barcode', 'internal_code']);
            $duplicate->name = "Cópia de {$product->name}";
            $duplicate->slug = Str::slug("copia-{$product->slug}").'-'.Str::random(4);
            $duplicate->status = 'draft';
            $duplicate->is_visible_in_catalog = false;
            $duplicate->save();

            foreach ($product->variants as $variant) {
                $newVariant = $variant->replicate(['sku', 'stock_quantity', 'reserved_quantity']);
                $newVariant->product_id = $duplicate->id;
                $newVariant->sku = strtoupper(Str::random(4)).'-'.strtoupper(Str::random(6));
                $newVariant->stock_quantity = 0;
                $newVariant->reserved_quantity = 0;
                $newVariant->save();
            }

            return $duplicate->fresh(['variants']);
        }, 3);
    }
}
```

**`app/Domain/Product/Services/UploadProductMediaService.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Product\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UploadProductMediaService
{
    public function handle(Product $product, UploadedFile $file, ?string $variantUuid = null): Media
    {
        if ($variantUuid) {
            $variant = ProductVariant::where('uuid', $variantUuid)
                ->where('product_id', $product->id)
                ->firstOrFail();

            return $variant->addMedia($file)->toMediaCollection('photos');
        }

        return $product->addMedia($file)->toMediaCollection('photos');
    }
}
```

- **MIRROR**: SERVICE_PATTERN
- **GOTCHA**: `DB::transaction(..., 3)` sempre. `$model->fresh()` antes de retornar. Side effects fora da closure. `replicate()` aceita array de campos a excluir na duplicação.

---

### Task 7: Criar Form Requests

- **ACTION**: Criar todos os FormRequests com validação PT-BR
- **IMPLEMENT**:

**`app/Http/Requests/Api/V1/Dashboard/Product/StoreProductRequest.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Product;

use App\Enums\ProductStatusEnum;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = Tenant::current()->getKey();

        return [
            'name'                   => ['required', 'string', 'max:255'],
            'status'                 => ['required', 'string', Rule::in(ProductStatusEnum::values())],
            'short_description'      => ['nullable', 'string', 'max:500'],
            'full_description'       => ['nullable', 'string'],
            'gender'                 => ['nullable', 'string'],
            'product_type'           => ['nullable', 'string', 'max:100'],
            'category_id'            => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id'               => ['nullable', 'integer', 'exists:brands,id'],
            'collection_id'          => ['nullable', 'integer', 'exists:collections,id'],
            'base_cost'              => ['required', 'numeric', 'min:0'],
            'base_price'             => ['required', 'numeric', 'min:0'],
            'promotional_price'      => ['nullable', 'numeric', 'min:0'],
            'desired_margin'         => ['nullable', 'numeric'],
            'commission'             => ['nullable', 'numeric', 'min:0'],
            'weight'                 => ['nullable', 'numeric', 'min:0'],
            'unit'                   => ['nullable', 'string', 'max:20'],
            'sku'                    => ['nullable', 'string', 'max:100',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'barcode'                => ['nullable', 'string', 'max:100'],
            'internal_code'          => ['nullable', 'string', 'max:100'],
            'is_visible_in_catalog'  => ['boolean'],
            'is_available_in_pdv'    => ['boolean'],
            'allows_discount'        => ['boolean'],
            'min_stock'              => ['integer', 'min:0'],
            'max_stock'              => ['nullable', 'integer', 'min:0'],
            'reorder_point'          => ['integer', 'min:0'],
            'storage_location'       => ['nullable', 'string', 'max:255'],
            'composition'            => ['nullable', 'string'],
            'care_instructions'      => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'O nome do produto é obrigatório.',
            'name.max'               => 'O nome não pode ter mais de :max caracteres.',
            'status.required'        => 'O status é obrigatório.',
            'status.in'              => 'O status informado é inválido.',
            'base_cost.required'     => 'O custo de compra é obrigatório.',
            'base_cost.numeric'      => 'O custo deve ser um valor numérico.',
            'base_price.required'    => 'O preço de venda é obrigatório.',
            'base_price.numeric'     => 'O preço deve ser um valor numérico.',
            'sku.unique'             => 'Este SKU já está cadastrado.',
            'category_id.exists'     => 'Categoria não encontrada.',
            'brand_id.exists'        => 'Marca não encontrada.',
            'collection_id.exists'   => 'Coleção não encontrada.',
            'min_stock.integer'      => 'O estoque mínimo deve ser um número inteiro.',
            'max_stock.integer'      => 'O estoque máximo deve ser um número inteiro.',
        ];
    }
}
```

**`app/Http/Requests/Api/V1/Dashboard/Product/UploadProductMediaRequest.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Product;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductMediaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'photo'        => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'variant_uuid' => ['nullable', 'string', 'exists:product_variants,uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'A foto é obrigatória.',
            'photo.image'    => 'O arquivo deve ser uma imagem.',
            'photo.mimes'    => 'A foto deve ser JPEG, PNG ou WebP.',
            'photo.max'      => 'A foto não pode ter mais de 5MB.',
            'variant_uuid.exists' => 'Variação não encontrada.',
        ];
    }
}
```

- **MIRROR**: FORM_REQUEST_PATTERN
- **GOTCHA**: SKU unique usa `Rule::unique(...)->where()` com `tenant_id` do tenant atual — exatamente como o email único por tenant no boilerplate. Para `UpdateRequest`, adicionar `->ignore($this->product->id)` no `Rule::unique`.

---

### Task 8: Criar Resources

- **ACTION**: Criar resources para todos os models
- **IMPLEMENT**:

**`app/Http/Resources/Api/V1/Dashboard/Product/ProductResource.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Dashboard\Product;

use App\Http\Resources\Api\V1\Dashboard\Brand\BrandResource;
use App\Http\Resources\Api\V1\Dashboard\Category\CategoryResource;
use App\Http\Resources\Api\V1\Dashboard\Collection\CollectionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                   => $this->uuid,
            'name'                   => $this->name,
            'slug'                   => $this->slug,
            'short_description'      => $this->short_description,
            'full_description'       => $this->full_description,
            'gender'                 => $this->gender?->value,
            'product_type'           => $this->product_type,
            'unit'                   => $this->unit,
            'base_cost'              => $this->base_cost,
            'base_price'             => $this->base_price,
            'promotional_price'      => $this->promotional_price,
            'desired_margin'         => $this->desired_margin,
            'sku'                    => $this->sku,
            'barcode'                => $this->barcode,
            'internal_code'          => $this->internal_code,
            'status'                 => $this->status->value,
            'is_visible_in_catalog'  => $this->is_visible_in_catalog,
            'is_available_in_pdv'    => $this->is_available_in_pdv,
            'allows_discount'        => $this->allows_discount,
            'is_featured'            => $this->is_featured,
            'is_new'                 => $this->is_new,
            'min_stock'              => $this->min_stock,
            'max_stock'              => $this->max_stock,
            'reorder_point'          => $this->reorder_point,
            'storage_location'       => $this->storage_location,
            'category'               => new CategoryResource($this->whenLoaded('category')),
            'brand'                  => new BrandResource($this->whenLoaded('brand')),
            'collection'             => new CollectionResource($this->whenLoaded('collection')),
            'variants_count'         => $this->whenCounted('variants'),
            'variants'               => ProductVariantResource::collection($this->whenLoaded('variants')),
            'photos'                 => $this->getMedia('photos')->map(fn ($media) => [
                'uuid'        => $media->uuid,
                'url'         => $media->getUrl(),
                'thumb_url'   => $media->getUrl('thumb'),
                'catalog_url' => $media->getUrl('catalog'),
                'name'        => $media->file_name,
                'size'        => $media->size,
            ]),
            'created_at' => $this->created_at?->format('d/m/Y H:i:s'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i:s'),
        ];
    }
}
```

**`app/Http/Resources/Api/V1/Dashboard/Product/ProductVariantResource.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Dashboard\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'product_uuid'      => $this->product?->uuid,
            'size'              => $this->size,
            'color'             => $this->color,
            'model'             => $this->model,
            'pattern'           => $this->pattern,
            'fabric'            => $this->fabric,
            'gender'            => $this->gender?->value,
            'sku'               => $this->sku,
            'barcode'           => $this->barcode,
            'cost'              => $this->cost,
            'price'             => $this->price,
            'effective_price'   => $this->effective_price,
            'effective_cost'    => $this->effective_cost,
            'stock_quantity'    => $this->stock_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->stock_quantity - $this->reserved_quantity,
            'is_active'         => $this->is_active,
            'photos'            => $this->getMedia('photos')->map(fn ($media) => [
                'uuid'      => $media->uuid,
                'url'       => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb'),
            ]),
            'created_at' => $this->created_at?->format('d/m/Y H:i:s'),
        ];
    }
}
```

- **MIRROR**: RESOURCE_PATTERN
- **GOTCHA**: `$this->whenLoaded()` evita N+1. `$this->whenCounted()` para contagens. Nunca expor `id`. Enum usa `->value`. Accessors calculados (`effective_price`) funcionam via `$this->effective_price` no resource.

---

### Task 9: Criar Controllers

- **ACTION**: Criar os 5 controllers
- **IMPLEMENT**:

**`app/Http/Controllers/Api/V1/Dashboard/ProductController.php`**:
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Domain\Product\Data\CreateProductData;
use App\Domain\Product\Data\UpdateProductData;
use App\Domain\Product\Services\CreateProductService;
use App\Domain\Product\Services\DeleteProductService;
use App\Domain\Product\Services\DuplicateProductService;
use App\Domain\Product\Services\UpdateProductService;
use App\Domain\Product\Services\UploadProductMediaService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Dashboard\Product\StoreProductRequest;
use App\Http\Requests\Api\V1\Dashboard\Product\UpdateProductRequest;
use App\Http\Requests\Api\V1\Dashboard\Product\UploadProductMediaRequest;
use App\Http\Resources\Api\V1\Dashboard\Product\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Products
 */
class ProductController extends ApiController
{
    public function index(): JsonResponse
    {
        $products = QueryBuilder::for(Product::class)
            ->allowedFilters(['status', 'gender', 'product_type', 'is_visible_in_catalog', 'is_available_in_pdv'])
            ->allowedIncludes(['category', 'brand', 'collection', 'variants'])
            ->withCount('variants')
            ->latest()
            ->paginate(20);

        return $this->paginated(
            $products->through(fn ($p) => new ProductResource($p)),
            'Produtos listados com sucesso'
        );
    }

    public function store(StoreProductRequest $request, CreateProductService $service): JsonResponse
    {
        $data = CreateProductData::from($request->validated());
        $product = $service->handle($data);

        return $this->created(new ProductResource($product), 'Produto criado com sucesso');
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'brand', 'collection', 'variants']);

        return $this->success(new ProductResource($product), 'Produto encontrado');
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductService $service): JsonResponse
    {
        $data = UpdateProductData::from($request->validated());
        $product = $service->handle($product, $data);

        return $this->success(new ProductResource($product), 'Produto atualizado com sucesso');
    }

    public function destroy(Product $product, DeleteProductService $service): JsonResponse
    {
        $service->handle($product);

        return $this->noContent();
    }

    public function duplicate(Product $product, DuplicateProductService $service): JsonResponse
    {
        $duplicate = $service->handle($product);

        return $this->created(new ProductResource($duplicate), 'Produto duplicado com sucesso');
    }

    public function uploadMedia(UploadProductMediaRequest $request, Product $product, UploadProductMediaService $service): JsonResponse
    {
        $media = $service->handle($product, $request->file('photo'), $request->input('variant_uuid'));

        return $this->created([
            'uuid'      => $media->uuid,
            'url'       => $media->getUrl(),
            'thumb_url' => $media->getUrl('thumb'),
        ], 'Foto enviada com sucesso');
    }

    public function deleteMedia(Product $product, string $mediaUuid): JsonResponse
    {
        $media = $product->getMedia('photos')->firstWhere('uuid', $mediaUuid);

        if (! $media) {
            return $this->notFound('Foto não encontrada');
        }

        $media->delete();

        return $this->noContent();
    }
}
```

- **MIRROR**: CONTROLLER_PATTERN
- **GOTCHA**: Route model binding usa `uuid` por causa do `getRouteKeyName()`. O `BelongsToTenant` global scope garante isolamento automático — produto de outro tenant retorna 404 naturalmente sem código extra.

---

### Task 10: Atualizar Rotas

- **ACTION**: Adicionar rotas no grupo `auth:sanctum` em `routes/api.php`
- **IMPORTS**: Adicionar os 5 controllers no topo do arquivo
- **IMPLEMENT**:

```php
// Adicionar imports no topo:
use App\Http\Controllers\Api\V1\Dashboard\CategoryController;
use App\Http\Controllers\Api\V1\Dashboard\BrandController;
use App\Http\Controllers\Api\V1\Dashboard\CollectionController;
use App\Http\Controllers\Api\V1\Dashboard\ProductController;
use App\Http\Controllers\Api\V1\Dashboard\ProductVariantController;

// Adicionar DENTRO do grupo middleware('auth:sanctum'):
Route::apiResource('categories', CategoryController::class);
Route::apiResource('brands', BrandController::class);
Route::apiResource('collections', CollectionController::class);
Route::apiResource('products', ProductController::class);
Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate']);
Route::post('products/{product}/media', [ProductController::class, 'uploadMedia']);
Route::delete('products/{product}/media/{mediaUuid}', [ProductController::class, 'deleteMedia']);
Route::apiResource('products.variants', ProductVariantController::class)->shallow();
```

- **GOTCHA**: `->shallow()` no apiResource aninhado faz que `show/update/destroy` de variante usem apenas `{variant}` sem `{product}` — mais limpo. As rotas `create/store` ainda incluem `{product}`.
- **VALIDATE**: `vendor/bin/sail artisan route:list --path=products --except-vendor`

---

### Task 11: Criar Testes

- **ACTION**: Criar 5 arquivos de teste cobrindo CRUD + isolamento de tenant
- **IMPLEMENT**:

```bash
vendor/bin/sail artisan make:test --pest CategoryTest --no-interaction
vendor/bin/sail artisan make:test --pest BrandTest --no-interaction
vendor/bin/sail artisan make:test --pest CollectionTest --no-interaction
vendor/bin/sail artisan make:test --pest ProductTest --no-interaction
vendor/bin/sail artisan make:test --pest ProductVariantTest --no-interaction
```

**`tests/Feature/ProductTest.php`** (testes essenciais):
```php
<?php

use App\Enums\ProductStatusEnum;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
    $this->user = User::factory()->create();
    tenantActingAs($this->user);
});

it('lists products paginated', function () {
    Product::factory()->count(3)->create();

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson('/api/v1/products');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['meta' => ['pagination']]);
});

it('creates a product with required fields', function () {
    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/products', [
            'name'                  => 'Legging Preta',
            'status'                => ProductStatusEnum::Active->value,
            'base_cost'             => 45.00,
            'base_price'            => 129.90,
            'is_visible_in_catalog' => true,
            'is_available_in_pdv'   => true,
            'allows_discount'       => true,
            'min_stock'             => 2,
            'reorder_point'         => 5,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Legging Preta')
        ->assertJsonPath('data.status', 'active');

    expect(Product::where('name', 'Legging Preta')->exists())->toBeTrue();
});

it('rejects duplicate sku within same tenant', function () {
    Product::factory()->create(['sku' => 'LEG-001']);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/products', [
            'name'                  => 'Outro Produto',
            'status'                => 'active',
            'base_cost'             => 10,
            'base_price'            => 30,
            'sku'                   => 'LEG-001',
            'is_visible_in_catalog' => false,
            'is_available_in_pdv'   => true,
            'allows_discount'       => true,
            'min_stock'             => 0,
            'reorder_point'         => 0,
        ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['sku']]);
});

it('allows same sku in different tenants', function () {
    $tenant2 = Tenant::factory()->create();
    $tenant2->makeCurrent();
    Product::factory()->create(['sku' => 'LEG-001', 'tenant_id' => $tenant2->id]);

    $this->tenant->makeCurrent();
    tenantActingAs($this->user);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/products', [
            'name'                  => 'Produto Tenant 1',
            'status'                => 'active',
            'base_cost'             => 10,
            'base_price'            => 30,
            'sku'                   => 'LEG-001',
            'is_visible_in_catalog' => false,
            'is_available_in_pdv'   => true,
            'allows_discount'       => true,
            'min_stock'             => 0,
            'reorder_point'         => 0,
        ]);

    $response->assertCreated();
});

it('duplicates product as draft with zero stock', function () {
    $product = Product::factory()->create(['name' => 'Original']);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson("/api/v1/products/{$product->uuid}/duplicate");

    $response->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.is_visible_in_catalog', false);

    expect(Product::where('name', 'Cópia de Original')->exists())->toBeTrue();
});

it('cannot access product from another tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $otherTenant->makeCurrent();
    $otherProduct = Product::factory()->create(['tenant_id' => $otherTenant->id]);

    $this->tenant->makeCurrent();
    tenantActingAs($this->user);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson("/api/v1/products/{$otherProduct->uuid}")
        ->assertNotFound();
});

it('soft deletes product', function () {
    $product = Product::factory()->create();

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->deleteJson("/api/v1/products/{$product->uuid}")
        ->assertNoContent();

    expect(Product::find($product->id))->toBeNull();
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
});

it('rejects invalid status', function () {
    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/products', [
            'name'      => 'Produto',
            'status'    => 'invalid_status',
            'base_cost' => 10,
            'base_price' => 30,
        ])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['status']]);
});
```

**`tests/Feature/ProductVariantTest.php`** (testes essenciais):
```php
<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
    $this->user = User::factory()->create();
    tenantActingAs($this->user);
    $this->product = Product::factory()->create(['sku' => 'LEG']);
});

it('creates variant with auto-generated sku', function () {
    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson("/api/v1/products/{$this->product->uuid}/variants", [
            'size'      => 'M',
            'color'     => 'Preto',
            'is_active' => true,
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $sku = $response->json('data.sku');
    expect($sku)->toContain('LEG');
    expect($sku)->toContain('M');
});

it('lists variants of a product', function () {
    ProductVariant::factory()->count(3)->create(['product_id' => $this->product->id]);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson("/api/v1/products/{$this->product->uuid}/variants");

    $response->assertOk()->assertJsonCount(3, 'data');
});

it('returns available quantity as stock minus reserved', function () {
    $variant = ProductVariant::factory()->create([
        'product_id'        => $this->product->id,
        'stock_quantity'    => 10,
        'reserved_quantity' => 3,
    ]);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson("/api/v1/variants/{$variant->uuid}");

    $response->assertOk()
        ->assertJsonPath('data.available_quantity', 7);
});
```

- **MIRROR**: TEST_STRUCTURE
- **GOTCHA**: Sempre usar `tenantActingAs()` + `withHeader('X-Tenant-ID', ...)`. O teste de isolamento de tenant é crítico para segurança — não pular.

---

### Task 12: Rodar Pint e Suite Completa

- **ACTION**: Formatar e validar
- **IMPLEMENT**:
```bash
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail artisan test --compact
```
- **VALIDATE**: Zero erros de Pint. Todos os testes (existentes + novos) passam.

---

## Testing Strategy

### Unit Tests

| Teste | Entrada | Saída esperada | Edge case? |
|-------|---------|----------------|-----------|
| Lista produtos paginados | 3 produtos no tenant | 200 + 3 itens + meta.pagination | |
| Cria produto com campos mínimos | name, status, custo, preço | 201 + uuid | |
| Rejeita name em branco | sem name | 422 + errors.name em PT-BR | |
| Rejeita status inválido | status='invalido' | 422 + errors.status | |
| Rejeita SKU duplicado no mesmo tenant | sku já cadastrado | 422 + errors.sku | |
| Permite SKU igual em outro tenant | sku de outro tenant | 201 | Crítico |
| Não acessa produto de outro tenant | uuid de outro tenant | 404 | Crítico |
| Soft delete de produto | DELETE /products/uuid | 204 + ausente no index | |
| Duplica produto | POST /products/uuid/duplicate | 201 + status=draft + stock=0 | |
| Cria variante com SKU auto | size+color | 201 + sku contendo partes | |
| Cria subcategoria | parent_id válido | 201 + parent_uuid | |
| Quantidade disponível = stock - reserved | stock=10, reserved=3 | available=7 | |

### Edge Cases Checklist
- [ ] SKU duplicado no mesmo tenant → rejeitar (422)
- [ ] SKU igual em tenants diferentes → permitir (201)
- [ ] Categoria raiz (sem parent) e subcategoria (com parent)
- [ ] Produto sem variações → CRUD funciona normalmente
- [ ] Variante sem size/color → SKU aleatório gerado sem erro
- [ ] Upload de foto > 5MB → rejeitar (422)
- [ ] Produto deletado (soft) não aparece no index
- [ ] Acesso a produto de outro tenant → 404 (não 403)
- [ ] base_price menor que base_cost → permitir (alertar via relatório, não bloquear)
- [ ] Duplicar produto com variações → variantes duplicadas com stock=0

---

## Validation Commands

```bash
# 1. Migrations
vendor/bin/sail artisan migrate --no-interaction

# 2. Rotas registradas
vendor/bin/sail artisan route:list --path=products --except-vendor

# 3. Pint (obrigatório após qualquer mudança PHP)
vendor/bin/sail bin pint --dirty --format agent

# 4. Testes por módulo
vendor/bin/sail artisan test --compact --filter=CategoryTest
vendor/bin/sail artisan test --compact --filter=BrandTest
vendor/bin/sail artisan test --compact --filter=CollectionTest
vendor/bin/sail artisan test --compact --filter=ProductTest
vendor/bin/sail artisan test --compact --filter=ProductVariantTest

# 5. Suite completa (regressão)
vendor/bin/sail artisan test --compact
```

---

## Acceptance Criteria

- [ ] 5 migrations criadas e rodando sem erro
- [ ] 5 models com `BelongsToTenant`, `HasUuid`, `SoftDeletes` corretos
- [ ] `Product` implementa `HasMedia` com coleção `photos` e conversões `thumb`/`catalog`
- [ ] `ProductVariant` tem SKU gerado automaticamente na criação
- [ ] CRUD completo para Category, Brand, Collection, Product, ProductVariant
- [ ] Tenant isolation: produto de outro tenant retorna 404
- [ ] SKU único por tenant (não global) para produtos; único global para variantes
- [ ] Subcategorias via `parent_id` funcionando
- [ ] `DuplicateProductService` cria cópia em `draft` com `stock=0` e `is_visible_in_catalog=false`
- [ ] Todos os testes passam
- [ ] Pint sem erros de formatação
- [ ] Nenhuma regressão nos 66 testes existentes

## Completion Checklist

- [ ] `declare(strict_types=1)` em todos os arquivos de `app/`
- [ ] `casts()` é método, nunca propriedade `$casts`
- [ ] `id`, `tenant_id`, `deleted_at` em `$hidden` em todos os models
- [ ] `getRouteKeyName()` retorna `'uuid'` em todos os models
- [ ] Mensagens em PT-BR em todos os FormRequests
- [ ] `DB::transaction(..., 3)` em todos os services com escrita
- [ ] `$model->fresh()` antes de retornar em todos os services
- [ ] `authorize()` retorna `true` em todos os FormRequests
- [ ] Nenhum `response()->json()` direto — sempre `ApiResponse` trait
- [ ] `vendor/bin/sail bin pint --dirty --format agent` rodado ao final

## Risks

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| Conflito de namespace no Auditable | Alta | Médio | Usar `\OwenIt\Auditing\Auditable` com namespace completo |
| SKU não único após insert concorrente | Baixa | Alto | `unique()` no banco garante integridade; transaction protege |
| Media Library sem storage nos testes | Média | Baixo | `Storage::fake('public')` nos testes de upload |
| Slug duplicado em criações rápidas | Baixa | Médio | Gerador de slug com loop de verificação no service |

## Notes

- `supplier_id` não existe ainda — será adicionado na Fase 2 via migration `add_supplier_id_to_products_table`
- `stock_quantity` em `product_variants` é gerenciado manualmente nesta fase. Fase 3 adiciona `StockMovement` que atualizará via eventos
- Autenticação e `ensure_tenant` já funcionam — não reimplementar
- O `slug` do produto é único por tenant via `unique(['tenant_id', 'slug'])` na migration
- Para atualizar produto, o `UpdateProductRequest` deve usar `Rule::unique(...)->ignore($this->route('product')->id)` para ignorar o próprio produto na validação de SKU
