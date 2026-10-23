<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookPolicy
{

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Book $book): Response
    {
        return $this->owns($user, $book);
    }

    public function update(User $user, Book $book): Response
    {
        return $this->owns($user, $book);
    }

    public function delete(User $user, Book $book): Response
    {
        return $this->owns($user, $book);
    }

    private function owns(User $user, Book $book): Response
    {
        return $user->id === $book->user_id
            ? Response::allow()
            : Response::denyAsNotFound('Resource not found.');
    }
}
