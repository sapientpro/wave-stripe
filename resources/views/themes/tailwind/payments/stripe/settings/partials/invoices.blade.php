@if (count(auth()->user()->invoices()))
    <table class="min-w-full overflow-hidden divide-y divide-gray-200 rounded-lg">
        <thead>
        <tr>
            <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase bg-gray-100">
                Date of Invoice
            </th>
            <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-right text-gray-500 uppercase bg-gray-100">
                Price
            </th>
            <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-right text-gray-500 uppercase bg-gray-100">
                Receipt Link
            </th>
        </tr>
        </thead>
        <tbody>
        @foreach(auth()->user()->invoices() as $invoice)
            <tr class="@if($loop->index%2 == 0){{ 'bg-gray-50' }}@else{{ 'bg-gray-100' }}@endif">
                <td class="px-6 py-4 text-sm font-medium leading-5 text-gray-900 whitespace-no-wrap">
                    {{ Carbon\Carbon::parse($invoice->created)->toFormattedDateString() }}
                </td>
                <td class="px-6 py-4 text-sm font-medium leading-5 text-right text-gray-900 whitespace-no-wrap">
                    {{ $invoice->amount_paid / 100 }} {{ strtoupper($invoice->currency) }}
                </td>
                <td class="px-6 py-4 text-sm font-medium leading-5 text-right whitespace-no-wrap">
                    <a href="{{ $invoice->hosted_invoice_url }}" target="_blank" class="mr-2 text-indigo-600 hover:underline focus:outline-none">
                        Download
                    </a>
                </td>

            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <p>Sorry, there seems to be an issue retrieving your invoices or you may not have any invoices yet.</p>
@endif
