<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_index_lists_only_the_users_own_books(): void
    {
        Book::factory()->count(3)->for($this->user)->create();
        Book::factory()->count(2)->create(); // belong to other users

        $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'isbn', 'value']], 'links', 'meta']);
    }

    public function test_user_can_create_a_book(): void
    {
        $this->postJson('/api/v1/books', [
            'name' => 'Clean Code', 'isbn' => '9780132350884', 'value' => '32.50',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Clean Code')
            ->assertJsonPath('data.value', '32.50');

        $this->assertDatabaseHas('books', ['isbn' => '9780132350884', 'user_id' => $this->user->id]);
    }

    public function test_user_id_in_the_payload_is_ignored(): void
    {
        $other = User::factory()->create();

        $this->postJson('/api/v1/books', ['name' => 'Mine', 'user_id' => $other->id])->assertCreated();

        $this->assertDatabaseHas('books', ['name' => 'Mine', 'user_id' => $this->user->id]);
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_input_is_rejected(array $payload, string $field): void
    {
        $this->postJson('/api/v1/books', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidPayloads(): array
    {
        return [
            'missing name' => [['isbn' => '9780132350884'], 'name'],
            'isbn with letter' => [['name' => 'A', 'isbn' => '978013235088X'], 'isbn'],
            'isbn with hyphen' => [['name' => 'A', 'isbn' => '978-0132350884'], 'isbn'],
            'isbn wrong length' => [['name' => 'A', 'isbn' => '12345'], 'isbn'],
            'value not numeric' => [['name' => 'A', 'value' => 'abc'], 'value'],
            'value 3 decimals' => [['name' => 'A', 'value' => '1.234'], 'value'],
            'value negative' => [['name' => 'A', 'value' => '-5'], 'value'],
        ];
    }

    public function test_isbn_is_unique_per_user_only(): void
    {
        Book::factory()->for($this->user)->create(['isbn' => '9780132350884']);

        $this->postJson('/api/v1/books', ['name' => 'Dup', 'isbn' => '9780132350884'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isbn');

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/books', ['name' => 'Same book', 'isbn' => '9780132350884'])
            ->assertCreated();
    }

    public function test_user_can_view_update_and_delete_own_book(): void
    {
        $book = Book::factory()->for($this->user)->create();

        $this->getJson("/api/v1/books/{$book->id}")->assertOk()->assertJsonPath('data.id', $book->id);

        $this->patchJson("/api/v1/books/{$book->id}", ['value' => '9.99'])
            ->assertOk()
            ->assertJsonPath('data.value', '9.99');

        $this->deleteJson("/api/v1/books/{$book->id}")->assertNoContent();
        $this->assertModelMissing($book);
    }

    public function test_user_cannot_access_another_users_book(): void
    {
        $book = Book::factory()->create(); // someone else's

        $this->getJson("/api/v1/books/{$book->id}")->assertNotFound();
        $this->patchJson("/api/v1/books/{$book->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/v1/books/{$book->id}")->assertNotFound();

        $this->assertModelExists($book);
    }

    public function test_not_yours_and_not_existing_look_identical(): void
    {
        $book = Book::factory()->create();

        $this->getJson('/api/v1/books/999999')->assertNotFound()->assertJson(['message' => 'Resource not found.']);
        $this->getJson("/api/v1/books/{$book->id}")->assertNotFound()->assertJson(['message' => 'Resource not found.']);
    }
}
