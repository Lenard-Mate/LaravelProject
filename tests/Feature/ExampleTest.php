<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Question;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function insert_new_user_in_db()
    {
        $response = $this->post("/questions/ask", [
            'title' => 'Why this program doesnt run ?',
            'content' => 'ceva sau altceva!!!',

            'slug' => 'why-this-program-doesnt-run',
            'status' => 1,
            'created_by' => 1,
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        $response->assertOk();
        $this->assertCount(1, Question::all());
    }
}
