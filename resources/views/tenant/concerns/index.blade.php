@extends('layouts.app')

@section('title', 'My Concerns')
@section('page-title', 'My Concerns')
@section('page-subtitle', 'Report a problem or share a concern with the admin. For repairs, use Maintenance instead.')
@section('page-actions')
    <a href="{{ route('tenant.concerns.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Send Concern</a>
@endsection

@section('content')
    @if (! $tenant)
        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200 text-sm text-slate-600">
            {{ $approvalPending ? 'Your account is still waiting for admin approval.' : 'Your account is not linked to a tenant record yet.' }}
        </div>
    @else
        <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Sent</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($concerns as $concern)
                        <tr>
                            <td class="px-4 py-3 max-w-md truncate font-medium">
                                {{ $concern->subject }}
                                @if ($concern->admin_reply)
                                    <span class="ml-1 text-xs text-slate-500">· Replied</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $concern->categoryLabel() }}</td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('-', ' ', $concern->status) }}</td>
                            <td class="px-4 py-3">{{ $concern->created_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('tenant.concerns.show', $concern) }}" class="font-medium text-slate-800 hover:underline">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No concerns sent yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $concerns->links() }}</div>
    @endif
@endsection
