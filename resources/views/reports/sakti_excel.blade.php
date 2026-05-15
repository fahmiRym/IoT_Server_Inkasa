@php
    $tLimit = env('SENSOR_TEMP_LIMIT', 35);
    $sLimit = env('SENSOR_SMOKE_LIMIT', 1000);
@endphp
<table>
    <thead>
        <!-- Header Perusahaan -->
        <tr>
            <th></th>
            <th></th>
            <th colspan="4" style="font-weight: bold; font-size: 14px; color: #004d60;">PT. INKASA JAYA ALUMINIUM</th>
        </tr>
        <tr>
            <th></th>
            <th></th>
            <th colspan="4">Jl. Raya Winong Km 1,5, Pasuruan, Indonesia</th>
        </tr>

        <tr>
            <th colspan="6"></th>
        </tr> <!-- Spacer -->

        <!-- Judul Laporan -->
        <tr>
            <th colspan="11" align="center"
                style="font-weight: bold; font-size: 18px; background-color: #004d60; color: #ffffff;">MONITORING NOC
                COMMAND CENTER</th>
        </tr>
        <tr>
            <th colspan="11" align="center"
                style="font-weight: bold; font-size: 14px; background-color: #004d60; color: #ffffff;">
                {{ strtoupper($periode_label) }}</th>
        </tr>

        <tr>
            <th colspan="11"></th>
        </tr> <!-- Spacer -->

        <!-- Info Tambahan -->
        <tr>
            <th colspan="11" align="left" style="font-style: italic;">Dicetak pada: {{ now()->format('d M Y H:i:s') }} |
                Versi: Sakti-ULTIMATE</th>
        </tr>

        <tr>
            <th colspan="11"></th>
        </tr> <!-- Spacer -->

        <!-- Header Tabel -->
        <tr>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                NO</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                TIME STAMP</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                TEMP (°C)</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                HUM (%)</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                SMOKE (AVG)</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                S1</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                S2</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                S3</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                F1</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                F2</th>
            <th
                style="font-weight: bold; border: 2px solid #000000; background-color: #002d33; color: #ffffff; text-align: center;">
                NODE STATUS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $row)
            @php
                $isBahaya = ($row->temp > $tLimit || $row->smoke > $sLimit);
                $bg = ($index % 2 == 1) ? '#f8f9fa' : '#ffffff';
                if ($isBahaya)
                    $bg = '#fff0f3';
            @endphp
            <tr>
                <td align="center" style="border: 1px solid #000000; background-color: {{ $bg }}">{{ $index + 1 }}</td>
                <td align="center" style="border: 1px solid #000000; background-color: {{ $bg }}">
                    {{ $row->created_at->format('d/m/Y H:i:s') }}</td>
                <td align="center"
                    style="border: 1px solid #000000; background-color: {{ $bg }}; color: {{ $row->temp > $tLimit ? '#e63946' : '#000000' }}">
                    {{ number_format($row->temp, 1) }}</td>
                <td align="center" style="border: 1px solid #000000; background-color: {{ $bg }}">
                    {{ number_format($row->hum, 1) }}</td>
                <td align="center"
                    style="border: 1px solid #000000; background-color: {{ $bg }}; color: {{ $row->smoke > $sLimit ? '#e63946' : '#000000' }}">
                    {{ $row->smoke }}</td>
                <td align="center" style="border: 1px solid #000000; background-color: {{ $bg }}">{{ $row->smoke1 ?? 0 }}
                </td>
                <td align="center" style="border: 1px solid #000000; background-color: {{ $bg }}">{{ $row->smoke2 ?? 0 }}
                </td>
                <td align="center" style="border: 1px solid #000000; background-color: {{ $bg }}">{{ $row->smoke3 ?? 0 }}
                </td>
                <td align="center"
                    style="border: 1px solid #000000; background-color: {{ $bg }}; color: {{ $row->flame1 ? '#e63946' : '#000000' }}">
                    {{ $row->flame1 ? 'FIRE' : 'SAFE' }}</td>
                <td align="center"
                    style="border: 1px solid #000000; background-color: {{ $bg }}; color: {{ $row->flame2 ? '#e63946' : '#000000' }}">
                    {{ $row->flame2 ? 'FIRE' : 'SAFE' }}</td>
                <td align="center"
                    style="border: 1px solid #000000; background-color: {{ $bg }}; font-weight: bold; color: {{ $isBahaya ? '#d32f2f' : '#2e7d32' }}">
                    {{ $isBahaya ? 'CRITICAL' : 'STABLE' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>