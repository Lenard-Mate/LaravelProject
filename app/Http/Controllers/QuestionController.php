<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Faker\Factory as Faker;
use Carbon\Carbon;

use App\Question;
use App\Answer;
use App\Tag;
use App\UserVote;

//use Carbon;
// use Config;
// use Auth;
// use Response;
// use DB;

class QuestionController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */

  protected function countUpVoteByVoteId($vote_id, $vote_category)
  {

    $up_vote = UserVote::where('vote_id', $vote_id)
      ->where('vote_type', Config::get('constants.VOTE_TYPE.UP_VOTE'))
      ->where('vote_category', $vote_category)
      ->count() ?? 0;
    return $up_vote;
  }

  public function showAsk()
  {
    if (Auth::user()) {
      return view('question.ask');
    } else {
      return redirect("/login");
    }
  }

  public function ask()
  {
    $question = new Question;

    $question->title = Input::get('title');

    $question->slug = str_slug($question->title, '-');

    $question->content = Input::get('content');

    $question->status = Config::get('constants.QUESTION_STATUS.ACTIVE');

    $question->created_by = Auth::user()->id;
    $question->save();

    foreach (explode(',', strtolower(Input::get('tag'))) as $key => $value) {
      $tag = new Tag;
      $tag->name = $value;
      $tag->question_id = $question->id;
      $tag->save();
    }

    $question_url = Config::get('constants.QUESTION_URL') . '/' . $question->id . '/' . $question->slug;

    return redirect($question_url);
  }

  public function deleteQuestion($question_id)
  {
    $questionForDelete = Question::findOrFail($question_id);
    $questionForDelete->delete();
    $userController = new userController();
    return $userController->showProfile();
  }

  public function answer()
  {

    $question = Question::find(Input::get("question_id"));

    $answer = new Answer;
    $answer->content = Input::get("answer");
    $answer->question_id = $question->id;
    $answer->answer_id = 0;
    $answer->status = 1;
    $answer->created_by = Auth::user()->id;
    $answer->save();

    $question_url = Config::get('constants.QUESTION_URL') . '/' . $question->id . '/' . $question->slug;

    return redirect($question_url);
  }

  public function comment()
  {

    $answer_id = Input::get("answer_id");

    $answer = new Answer;
    $answer->content = Input::get("content");
    $answer->question_id = 0;
    $answer->answer_id = $answer_id;
    $answer->status = 1;
    $answer->created_by = Auth::user()->id;
    $answer->save();

    return Response::json(['status' => true, 'answer' => $answer]);
  }

  public function getQuestionById($id)
  {
    $question = Question::find($id);
    $question->asked_user = ($question->user)['name'];
    $question->asked_user_id = ($question->user)['id'];

    $question->tags = $question->tag;
    $question->asked_user_avatar = '/uploads/avatars/' . ($question->user)['avatar'];

    if (Auth::user()) {
      $question->up_voted = $this->findUserVoteByType($id, Config::get('constants.VOTE_CATEGORY.QUESTION'), Config::get('constants.VOTE_TYPE.UP_VOTE'));
      $question->down_voted = $this->findUserVoteByType($id, Config::get('constants.VOTE_CATEGORY.QUESTION'), Config::get('constants.VOTE_TYPE.DOWN_VOTE'));
    } else {
      $question->up_voted = false;
      $question->down_voted = false;
    }

    $question->formatted_created_at = $question->created_at->format('M-d-Y') . ' at ' . $question->created_at->format('h:i');

    $question->votes = $this->countUpVoteByVoteId($id, Config::get('constants.VOTE_CATEGORY.QUESTION'))
      - $this->countDownVoteByVoteId($id, Config::get('constants.VOTE_CATEGORY.QUESTION'));

    return Response::json($question);
  }

  public function getAnswersById($questionId)
  {

    $answers = Answer::with(array('children' => function ($query) {
      $query
        ->with(array('user' => function ($query1) {
          $query1->select('*');
        }))->select('*');
    }))
      ->with(array('user' => function ($query) {
        $query->select('*');
      }))
      ->where('answers.question_id', $questionId)
      ->orderBy('answers.status', 'desc')
      ->orderBy('answers.created_at', 'desc')
      ->get();

    foreach ($answers as $key => $answer) {

      if (Auth::user()) {
        $answers[$key]->up_voted = $this->findUserVoteByType($answer->id, Config::get('constants.VOTE_CATEGORY.ANSWER'), Config::get('constants.VOTE_TYPE.UP_VOTE'));
        $answers[$key]->down_voted = $this->findUserVoteByType($answer->id, Config::get('constants.VOTE_CATEGORY.ANSWER'), Config::get('constants.VOTE_TYPE.DOWN_VOTE'));
      } else {
        $answers[$key]->up_voted = false;
        $answers[$key]->down_voted = false;
      }

      $answers[$key]->formatted_created_at = $answers[$key]->created_at->format('M-d-Y') . ' at ' . $answers[$key]->created_at->format('h:i');

      $answers[$key]->votes = $this->countUpVoteByUserAndVoteId($answer->id, ($answer->user)['id'], Config::get('constants.VOTE_CATEGORY.ANSWER'))
        - $this->countDownVoteByUserAndVoteId($answer->id, ($answer->user)['id'], Config::get('constants.VOTE_CATEGORY.ANSWER'));
    }


    return Response::json($answers);
  }

  public function showQuestionList()
  {
    $this->completeQuestionWithTags();
    $questions = Question::orderBy('created_at', 'desc')->paginate(10);

    $newest_questions = Question::orderBy('created_at', 'desc')->paginate(20);

    $question_count = Question::count();

    foreach ($questions as $key => $question) {
      $questions[$key]->votes = $this->countUpVoteByVoteId($question->id, Config::get('constants.VOTE_CATEGORY.QUESTION'))
        - $this->countDownVoteByVoteId($question->id, Config::get('constants.VOTE_CATEGORY.QUESTION'));

      $questions[$key]->answers = $this->countAnswerByQuestionId($question->id);
    }

    $top_tags = $this->getTopTags();
    $userId = Auth::id();
    $isAdmin = false;
    $text = "All Questions";
    if (Auth::user()) $isAdmin = Auth::user()->is_admin;
    //print_r(Auth::id());
    //$user = Auth::user();
    return view('question.list', [
      'questions' => $questions, 'newest_questions' => $newest_questions, 'question_count' => $question_count, 'top_tags' => $top_tags, 'isAdmin' => $isAdmin, 'text' => $text
    ]);
  }

  public function showEditAnswer($id)
  {

    $answer = Answer::find($id);
    if (!Auth::check() || (Auth::user()->id != $answer->created_by)) {
      return redirect('/questions/list');
    }

    return view('question.edit_answer', ['answer' => $answer]);
  }

  public function showEditQuestion($id)
  {

    $question = Question::find($id);

    if (!Auth::check() || (Auth::user()->id != $question->created_by)) {
      return redirect('/questions/list');
    }

    $tags = [];

    foreach ($question->tag as $key => $value) {
      $tags[] = $value->name;
    }
    $question->tags = implode(',', $tags);
    return view('question.edit_question', ['question' => $question]);
  }

  public function editQuestion()
  {

    $id = Input::get('id');

    $question = Question::find($id);

    $question->title = Input::get('title');

    $question->slug = str_slug($question->title, '-');

    $question->content = Input::get('content');

    $question->save();

    foreach ($question->tag as $key => $value) {
      $tag = Tag::find($value->id);
      $tag->delete();
    }
    foreach (explode(',', strtolower(Input::get('tag'))) as $key => $value) {
      $tag = new Tag;
      $tag->name = $value;
      $tag->question_id = $question->id;
      $tag->save();
    }

    $question_url = Config::get('constants.QUESTION_URL') . '/' . $question->id . '/' . $question->slug;

    return redirect($question_url);
  }

  public function editAnswer()
  {
    $id = Input::get('id');

    $answer = Answer::find($id);
    $answer->content = Input::get('content');
    $answer->save();

    $question = Question::find($answer->question_id);
    $question_url = Config::get('constants.QUESTION_URL') . '/' . $question->id . '/' . $question->slug;

    return redirect($question_url);
  }

  public function manageSearch(Request $request)
  {
    $input = $request->get('inputul');
    //cautarea
    $questions1 = Question::whereHas('tag', function ($query) use ($input) {
      $query->where('name', '=', $input);
    })
      ->orderBy('created_at', 'desc')->paginate(10);

    foreach ($questions1 as $key => $question) {
      $questions1[$key]->asked_user = ($question->user)['name'];

      $questions1[$key]->votes = $this->countUpVoteByVoteId($question->id, Config::get('constants.VOTE_CATEGORY.QUESTION'))
        - $this->countDownVoteByVoteId($question->id, Config::get('constants.VOTE_CATEGORY.QUESTION'));

      $questions1[$key]->answers = $this->countAnswerByQuestionId($question->id);
    }
    //"%{$value}%"
    $questions2 = Question::query()->where('title', 'LIKE', "%{$input}%", 'or', 'content', 'LIKE', "%{$input}%")->orderBy('created_at', 'desc')->paginate(10);

    foreach ($questions2 as $key => $question) {
      $questions2[$key]->asked_user = ($question->user)['name'];
      $questions2[$key]->answers = $this->countAnswerByQuestionId($question->id);
    }
    $mayBeInterested = array();
    //array_push($stack, "apple", "raspberry");
    foreach ($this->tagurile as $key => $val) {
      if ($val == $input) {
        array_push(
          $mayBeInterested,
          $this->tagurile[$key - 3],
          $this->tagurile[$key - 2],
          $this->tagurile[$key - 1],
          $this->tagurile[$key + 1],
          $this->tagurile[$key + 2],
          $this->tagurile[$key + 3]
        );
        break;
      }
    }



    return view('searching.search',  ['questions1' => $questions1, 'questions2' => $questions2, 'tag' => $input, 'mayBeInterested' => $mayBeInterested]);
  }


  public function seRegasesteInTaguri($question_id)
  {
    $allTags = Tag::all();
    foreach ($allTags as $key => $val) {
      if ($allTags[$key]->question_id == $question_id) {
        return true;
      }
    }

    // for ($j = 0; $j < count($allTags) - 1; $j++) {
    //   // print_r($allTags[$j]->question_id);
    //   // echo '<br/>';
    //   if ($allTags[$j + 1]->question_id === $question_id) {
    //     return true;
    //   }
    // }
    // print_r("final false");
    return false;
  }


  // public $tagurile = array(
  //   'php', 'c', 'c++', 'java', 'c#', 'javascript', 'typescript', 'sql', 'maria_db', 'ajax',
  //   'jwt', 'node js', 'react', 'vue', 'angular', 'android', 'apache', 'windows', 'linux',
  //   'gradle', 'html', 'css', 'blade', 'laravel', 'rest api', 'graph ql', 'merkle tree', 'quees', 'DHCP',
  //   'rest api', 'ok http', 'retrofit', 'override', 'inputstram', 'data types', 'static vs mutable', 'private vs protected', 'regex', 'input-form',
  //   'sockets', 'threads', 'RMI', 'arraylist', 'matrix', 'canvas', 'vectors', 'json', 'xml', 'fetch request', 'get vs post', 'go', 'scala', 'godot', 'faker', 'unity',
  //   'addmob', 'conversion units', 'stack', 'mongo-db', 'maven', 'cassandra', 'springboot', 'intelij idea', 'netbeans', 'code blocks', 'softweare develeoment', 'students projects',
  //   'hosting', 'scoping in js', 'security', 'unit testing', 'github', 'micorservices', 'clean architecture', 'mvc', 'mathlab', 'assembly', 'arduino', 'OOP', 'domain driven design',
  //   'packet tracer'
  // );


  public $tagurile = array(
    'unu', 'doi', 'trei', 'patru',
    'php', 'javascript', 'typescript', 'ajax', 'react', 'vue', 'angular', 'json', 'xml', 'fetch request', 'get vs post',
    'node js', 'html', 'css', 'blade', 'canvas', 'regex', 'input-form', 'hosting', 'scoping in js', 'security', 'unit testing',
    'github', 'jwt', 'laravel', 'rest api', 'graph ql', 'ok http', 'retrofit', 'faker', 'apache', 'c', 'c++', 'java', 'maven',
    'override', 'inputstram', 'static vs mutable', 'private vs protected', 'sockets', 'threads', 'RMI', 'c #', 'android', 'gradle',
    'go', 'unity', 'scala', 'mathlab', 'assembly', 'arduino', 'godot', 'sql', 'maria_db', 'mongo-db', 'cassandra', 'windows',
    'linux', 'data types', 'quees', 'arraylist', 'matrix', 'vectors', 'stack', 'merkle tree', 'springboot', 'intelij idea', 'netbeans',
    'code blocks', 'softweare develeoment', 'students projects', 'DHCP', 'packet tracer', 'addmob', 'conversion units', 'micorservices',
    'clean architecture', 'mvc', 'OOP', 'domain driven design', 'unu', 'doi', 'trei', 'patru'
  );

  public function makeANewTag($id)
  {

    // echo '<br/>';
    // print_r("fac un nou tag");
    //global $tagurile;
    $faker = Faker::create();
    $auxiliar = $faker->numberBetween(4, 66);
    // echo '<br/>';
    // print_r("!!!!!!");
    // echo '<br/>';
    // //print_r($GLOBALS['tagurile']);   oare memory leak?
    // print_r($this->tagurile);
    // echo '<br/>';
    // print_r("!!!!!!");
    // echo '<br/>';
    $newTag = new Tag;
    $newTag->name = $this->tagurile[$auxiliar];
    $newTag->question_id = $id;
    $newTag->save();
    return;
  }

  //urmatoarea functie completeza intrebarile generate random cu taguri,in cazul in care ele nu aveau asignat un tag
  public function completeQuestionWithTags()
  {
    // print_r("complete question tags");
    // echo '<br/>';
    $questions = Question::all();
    foreach ($questions as $key => $val) {
      if ($this->seRegasesteInTaguri($key + 1) == false) {
        // print_r("o cheie negasita este");
        // print_r($key + 1);
        // echo '<br/>';
        $this->makeANewTag($key + 1);
      }
    }
    // for ($i = 0; $i < count($questions); $i++) {
    //   if ($this->seRegasesteInTaguri($i + 1) === false) {
    //     echo '<br/>';
    //     print_r($i + 1);
    //     $this->makeANewTag($i + 1);
    //   }
    // }
    return;
  }

  public function showQuestionByTag($tag)
  {

    $questions = Question::whereHas('tag', function ($query) use ($tag) {
      $query->where('name', '=', $tag);
    })
      ->orderBy('created_at', 'desc')->paginate(10);

    $newest_questions = Question::orderBy('created_at', 'desc')->paginate(20);

    $question_count = Question::count();

    foreach ($questions as $key => $question) {
      $questions[$key]->votes = $this->countUpVoteByVoteId($question->id, Config::get('constants.VOTE_CATEGORY.QUESTION'))
        - $this->countDownVoteByVoteId($question->id, Config::get('constants.VOTE_CATEGORY.QUESTION'));

      $questions[$key]->answers = $this->countAnswerByQuestionId($question->id);
    }

    $top_tags = $this->getTopTags();

    $text = "Questions with tag " . '"' . $tag . '"';

    return view('question.list', [
      'questions' => $questions, 'newest_questions' => $newest_questions, 'question_count' => $question_count, 'top_tags' => $top_tags, 'text' => $text
    ]);
  }


  public function showQuestionDetail($id, $slug)
  {

    $currentUserId = 0;

    if (Auth::user()) {
      $currentUserId = Auth::user()->id;
    }

    $question = Question::find($id);

    if ($question) {
      $question->views++;
      $question->save();
    }

    $newest_questions = Question::orderBy('created_at', 'desc')->paginate(20);

    return view('question.detail', [
      'question' => $question,
      'newest_questions' => $newest_questions,
      'currentUserId' => $currentUserId,
      'isLogin' => $currentUserId > 0
    ]);
  }

  protected function findUserVote($vote_id, $vote_category)
  {
    $user_vote = UserVote::where('vote_id', $vote_id)
      ->where('vote_by', Auth::user()->id)
      ->where('vote_category', $vote_category) // 0 question, 1 answer
      // ->where('vote_type', $vote_type)    // 0 vote, 1 downvote
      ->first();
    return $user_vote;
  }

  protected function findUserVoteByType($vote_id, $vote_category, $vote_type)
  {

    $user_vote = UserVote::where('vote_id', $vote_id)
      ->where('vote_by', Auth::user()->id)
      ->where('vote_category', $vote_category)
      ->where('vote_type', $vote_type)
      ->exists();
    return $user_vote;
  }

  protected function countUpVoteByUserAndVoteId($vote_id, $user_id, $vote_category)
  {
    $up_vote = UserVote::where('vote_id', $vote_id)
      // ->where('vote_by', $user_id)
      ->where('vote_type', Config::get('constants.VOTE_TYPE.UP_VOTE'))
      ->where('vote_category', $vote_category)
      ->count();
    return $up_vote;
  }

  protected function countDownVoteByUserAndVoteId($vote_id, $user_id, $vote_category)
  {
    $up_vote = UserVote::where('vote_id', $vote_id)
      // ->where('vote_by', $user_id)
      ->where('vote_type', Config::get('constants.VOTE_TYPE.DOWN_VOTE'))
      ->where('vote_category', $vote_category)
      ->count();
    return $up_vote;
  }



  protected function countDownVoteByVoteId($vote_id, $vote_category)
  {
    $up_vote = UserVote::where('vote_id', $vote_id)
      ->where('vote_type', Config::get('constants.VOTE_TYPE.DOWN_VOTE'))
      ->where('vote_category', $vote_category)
      ->count();
    return $up_vote;
  }

  protected function countAnswerByQuestionId($questionId)
  {

    $answers = Answer::where('answers.question_id', $questionId)
      ->count();
    return $answers;
  }

  protected function getTopTags()
  {

    $tags = DB::table('tags')
      ->select('name', DB::raw('count(question_id) as total'))
      ->groupBy('name')
      ->orderBy('total', 'desc')
      ->paginate(10);

    return $tags;
  }

  protected function setAllAnswersAcceptedToFalse($question_id)
  {
    Answer::where('answers.question_id', $question_id)
      ->update(['accepted' => false]);
  }

  protected function undoVoted($user_voted)
  {
    $user_voted->delete();
  }

  protected function insertUserVote($vote_id, $vote_category, $vote_type)
  {

    $new_vote = new UserVote;
    $new_vote->created_at = Carbon::now();
    $new_vote->vote_by = Auth::user()->id;
    $new_vote->vote_id = $vote_id;
    $new_vote->vote_category = $vote_category;
    $new_vote->vote_type = $vote_type;
    $new_vote->save();
  }

  public function voteAction()
  {

    $vote_id = Input::get('vote_id');
    $vote_type = Input::get('vote_type');
    $vote_category = Input::get('vote_category');


    $user_voted = $this->findUserVote($vote_id, $vote_category);

    if ($user_voted) {

      $isUndoVoted = $user_voted->vote_type == $vote_type;

      if ($isUndoVoted) {
        $this->undoVoted($user_voted);
      } else {
        $this->undoVoted($user_voted);
        $this->insertUserVote($vote_id, $vote_category, $vote_type);
      }
    } else {
      $this->insertUserVote($vote_id, $vote_category, $vote_type);
    }

    $up_votes = $this->countUpVoteByUserAndVoteId($vote_id, Auth::user()->id, $vote_category);
    $down_votes = $this->countDownVoteByUserAndVoteId($vote_id, Auth::user()->id, $vote_category);

    $votes = $up_votes - $down_votes;
    $up_voted = $this->findUserVoteByType($vote_id, $vote_category, Config::get('constants.VOTE_TYPE.UP_VOTE'));
    $down_voted = $this->findUserVoteByType($vote_id, $vote_category, Config::get('constants.VOTE_TYPE.DOWN_VOTE'));

    return Response::json(['status' => true, 'votes' => $votes, 'up_voted' => $up_voted, 'down_voted' => $down_voted]);
  }

  public function acceptAnswer()
  {
    $answer_id = Input::get('answer_id');
    $question_id = Input::get('question_id');

    $question = Question::find($question_id);
    $anwser = Answer::find($answer_id);

    if (Auth::user()->id != $question->created_by) {
      return Response::json(['status' => false]);
    }

    $this->setAllAnswersAcceptedToFalse($question_id);

    $anwser->accepted = true;
    $anwser->save();


    return Response::json(['status' => true]);
  }
}
