<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <Worksheet ss:Name="Summary">
        <Table>
            @foreach ([
                ['Copier Revenue Report', ''],
                ['Scope', $report['scope_label']],
                ['Period', $report['period_label']],
                ['Date range', $report['from']->format('d/m/Y').' - '.$report['to']->format('d/m/Y')],
                ['Total revenue', number_format($report['summary']['total_revenue'], 2)],
                ['Mono revenue', number_format($report['summary']['mono_revenue'], 2)],
                ['Colour revenue', number_format($report['summary']['colour_revenue'], 2)],
                ['Total pages', number_format($report['summary']['total_pages'])],
                ['Included pages', number_format($report['summary']['included_total_pages'])],
                ['Chargeable pages', number_format($report['summary']['chargeable_total_pages'])],
            ] as $row)
                <Row><Cell><Data ss:Type="String">{{ $row[0] }}</Data></Cell><Cell><Data ss:Type="String">{{ $row[1] }}</Data></Cell></Row>
            @endforeach
        </Table>
    </Worksheet>
    <Worksheet ss:Name="Detail">
        <Table>
            <Row>
                @foreach (['Date', 'Client', 'Site', 'Machine', 'Service agreement', 'Agreement start', 'Agreement end', 'Mono pages', 'Colour pages', 'Total pages', 'Included mono', 'Included colour', 'Included total', 'Chargeable mono', 'Chargeable colour', 'Chargeable total', 'Mono PPC', 'Colour PPC', 'Mono revenue', 'Colour revenue', 'Total revenue'] as $heading)
                    <Cell><Data ss:Type="String">{{ $heading }}</Data></Cell>
                @endforeach
            </Row>
            @foreach ($report['rows'] as $row)
                <Row>
                    <Cell><Data ss:Type="String">{{ $row['date'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['client_name'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['site_name'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['machine_name'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['service_agreement_number'] ?? 'Legacy pricing' }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['service_agreement_starts_on'] ?? '' }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['service_agreement_ends_on'] ?? '' }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (int) $row['mono_usage'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (int) $row['colour_usage'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (int) $row['total_usage'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (int) $row['included_mono_pages'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (int) $row['included_colour_pages'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (int) $row['included_total_pages'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (int) $row['chargeable_mono_pages'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (int) $row['chargeable_colour_pages'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (int) $row['chargeable_total_pages'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (float) $row['mono_ppc'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (float) $row['colour_ppc'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (float) $row['mono_revenue'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (float) $row['colour_revenue'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ (float) $row['total_revenue'] }}</Data></Cell>
                </Row>
            @endforeach
        </Table>
    </Worksheet>
</Workbook>
