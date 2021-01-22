@extends('layouts.app')

<div class="flex-center position-ref full-height">
    <div class="content">
        <h1>Here is a list of all occurences for <strong> "{{$tag}}" </strong></h1>
        <h2>Search after <strong>"tag"</strong> leads to</h2>
        <table class="table table-bordered">
            <thead>
                <td><b>Number</b></td>
                <td><b>Question title</b></td>
                <td><b>Question content</b></td>
                <td><b>The person who post it</b></td>
                <td><b>Was posted in</b></td>
            </thead>
            <tbody>
                @foreach ($questions1 as $key => $question)
                <tr>
                    <td class="inner-table">{{++$key}}</td>
                    <td class="inner-table">{{$question->title}}</td>
                    <td class="inner-table">{{$question->content}}</td>
                    <td class="inner-table">{{$question->asked_user}}</td>
                    <td class="inner-table">{{$question->created_at}}</td>
                </tr>
                @endforeach
            </tbody>

        </table>
        <h2>Search after "<strong>question that contains this word or its description contains</strong>" leads to</h2>
        <table class="table table-bordered">
            <thead>
                <td><b>Number</b></td>
                <td><b>Question title</b></td>
                <td><b>Question content</b></td>
                <td><b>The person who post it</b></td>
                <td><b>Was posted in</b></td>
            </thead>
            <tbody>
                @foreach ($questions2 as $key => $question)
                <tr>
                    <td class="inner-table">{{++$key}}</td>
                    <td class="inner-table">{{$question->title}}</td>
                    <td class="inner-table">{{$question->content}}</td>
                    <td class="inner-table">{{$question->asked_user}}</td>
                    <td class="inner-table">{{$question->created_at}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="tag-list">
            <b> Moreover,maybe you are interested in: </b>
            @foreach ($mayBeInterested as $item)
            <p>
                <a href="/tag/{{ $item }}" class="item">
                    {{ $item}}
                </a>
            </p>
            @endforeach

        </div>

    </div>
</div>

<a href="/" class="pull-left btn btn-primary">Back to main page</a>