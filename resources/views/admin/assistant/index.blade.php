@extends('layouts.app')

@section('title', 'Ask QJ Bot')
@section('page-title', 'Ask QJ Bot')

@php
    $user = auth()->user();
    $userAvatar = $user->avatar_path ? $user->avatarUrl() : asset('images/default-avatar.jpg');
@endphp

@section('content')
    <section class="assistant" data-assistant data-user-avatar="{{ $userAvatar }}" aria-label="Chat with QJ Bot">
        <header class="assistant__header">
            @include('partials.qj-bot-avatar', ['class' => 'assistant__header-avatar'])
            <div class="min-w-0 flex-1">
                <h2>QJ Bot</h2>
                <p><span class="assistant__online" aria-hidden="true"></span>{{ $configured ? 'Online' : 'Offline' }}<span class="assistant__tagline">{{ $configured ? ' · your boarding house assistant' : '' }}</span></p>
            </div>
            <span class="assistant__pill" title="The free AI limit resets daily at 8:00 AM">
                <span data-assistant-remaining>{{ $remaining === null ? 'AI ready' : $remaining.' AI left' }}</span>
            </span>
            <a href="{{ route('admin.assistant.activity') }}" class="assistant__icon-button" aria-label="Bot activity log" title="Bot activity log">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg>
            </a>
            @if ($messages->isNotEmpty())
                <form method="POST" action="{{ route('admin.assistant.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button class="assistant__icon-button" aria-label="Clear chat" title="Clear chat">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14"/></svg>
                    </button>
                </form>
            @endif
        </header>

        <div class="assistant__log" data-assistant-log aria-live="polite">
            <div class="assistant__row assistant__row--assistant">
                @include('partials.qj-bot-avatar', ['class' => 'assistant__avatar'])
                <div class="assistant__msg"><p>{{ $briefing }}</p></div>
            </div>
            @foreach ($messages as $message)
                <div class="assistant__row assistant__row--{{ $message->role }}">
                    @if ($message->role === 'user')
                        <img src="{{ $userAvatar }}" alt="{{ $user->name }}" class="assistant__avatar">
                    @else
                        @include('partials.qj-bot-avatar', ['class' => 'assistant__avatar'])
                    @endif
                    <div class="assistant__msg">
                        <p>{{ $message->content }}</p>
                        @if ($message->actions->isNotEmpty())
                            <div class="action-cards" data-action-cards>
                                @foreach ($message->actions as $action)
                                    @include('admin.assistant._card', ['card' => $action->card()])
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <footer class="assistant__footer">
            <div class="assistant__quick" aria-label="Quick questions">
                <button type="button" class="assistant__chip assistant__chip--template" data-tenant-template-open>Copy tenant form</button>
                @foreach ($quick as $key => $label)
                    <form method="POST" action="{{ route('admin.assistant.quick') }}" data-assistant-quick>
                        @csrf
                        <input type="hidden" name="key" value="{{ $key }}">
                        <button type="submit" class="assistant__chip" data-question="{{ $label }}">{{ $label }}</button>
                    </form>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.assistant.ask') }}" class="assistant__composer" data-assistant-form>
                @csrf
                <label for="assistant-question" class="sr-only">Your question</label>
                <textarea id="assistant-question" name="question" rows="1" maxlength="2000" required autocomplete="off" enterkeyhint="send" placeholder="Ask QJ Bot… (Shift+Enter for a new line)"></textarea>
                <button type="submit" class="assistant__send" aria-label="Send" title="Send">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
                </button>
            </form>
            @error('question') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            <p class="assistant__note">Changes only happen when you press Confirm. QJ Bot can still make mistakes, so double-check before confirming.</p>
        </footer>

        <dialog class="assistant-template" data-tenant-template-dialog aria-labelledby="tenant-template-title">
            <div class="assistant-template__head">
                <div>
                    <h2 id="tenant-template-title">Tenant Messenger form</h2>
                    <p>I-copy ug i-send sa tenant. I-paste ang iyang completed reply balik sa QJ Bot.</p>
                </div>
                <button type="button" class="assistant-template__close" data-tenant-template-close aria-label="Close">&times;</button>
            </div>
            <pre>{{ $tenantTemplate }}</pre>
            <button type="button" class="assistant-template__copy" data-copy="{{ $tenantTemplate }}">Copy form</button>
        </dialog>
    </section>
@endsection
