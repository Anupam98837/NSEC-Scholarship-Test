@extends('pages.users.layout.structure')

@section('title', 'Auto Assign Quizzes')
@section('header', 'Auto Assign Quizzes')

@section('content')
  @include('modules.quizz.autoAssignQuizzes')
@endsection
