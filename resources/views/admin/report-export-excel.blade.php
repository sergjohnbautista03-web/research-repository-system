@php
    $cleanXml = fn ($value) => preg_replace('/[^\P{C}\t\n\r]/u', '', (string) $value);
    $generatedAt = now('Asia/Manila')->format('F j, Y g:i A');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
@endphp
<Workbook
    xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">
    <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
        <Title>Ube Repository Research Report</Title>
        <Author>Ube Repository</Author>
        <Created>{{ now('Asia/Manila')->toIso8601String() }}</Created>
    </DocumentProperties>

    <Styles>
        <Style ss:ID="Default" ss:Name="Normal">
            <Alignment ss:Vertical="Top"/>
            <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1A0638"/>
        </Style>
        <Style ss:ID="Title">
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:FontName="Calibri" ss:Size="18" ss:Bold="1" ss:Color="#2D0A5E"/>
        </Style>
        <Style ss:ID="Subtitle">
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#6B5B82"/>
        </Style>
        <Style ss:ID="MetaLabel">
            <Alignment ss:Vertical="Center"/>
            <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#6B2FA0"/>
            <Interior ss:Color="#F8F2FF" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2D5F4"/>
            </Borders>
        </Style>
        <Style ss:ID="MetaValue">
            <Alignment ss:Vertical="Center" ss:WrapText="1"/>
            <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#1A0638"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2D5F4"/>
            </Borders>
        </Style>
        <Style ss:ID="Header">
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
            <Interior ss:Color="#4B1D78" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#3B0F63"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#3B0F63"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#3B0F63"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#3B0F63"/>
            </Borders>
        </Style>
        <Style ss:ID="Cell">
            <Alignment ss:Vertical="Top" ss:WrapText="1"/>
            <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#1A0638"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
            </Borders>
        </Style>
        <Style ss:ID="CellCenter">
            <Alignment ss:Horizontal="Center" ss:Vertical="Top" ss:WrapText="1"/>
            <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#1A0638"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
            </Borders>
        </Style>
        <Style ss:ID="Empty">
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:FontName="Calibri" ss:Size="10" ss:Italic="1" ss:Color="#6B5B82"/>
            <Interior ss:Color="#FAF8FF" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E8DFF5"/>
            </Borders>
        </Style>
    </Styles>

    <Worksheet ss:Name="Research Report">
        <Table ss:DefaultRowHeight="18">
            <Column ss:Index="1" ss:AutoFitWidth="0" ss:Width="42"/>
            <Column ss:Index="2" ss:AutoFitWidth="0" ss:Width="330"/>
            <Column ss:Index="3" ss:AutoFitWidth="0" ss:Width="180"/>
            <Column ss:Index="4" ss:AutoFitWidth="0" ss:Width="190"/>
            <Column ss:Index="5" ss:AutoFitWidth="0" ss:Width="160"/>
            <Column ss:Index="6" ss:AutoFitWidth="0" ss:Width="70"/>

            <Row ss:Height="30">
                <Cell ss:MergeAcross="5" ss:StyleID="Title">
                    <Data ss:Type="String">Ube Repository Research Report</Data>
                </Cell>
            </Row>
            <Row ss:Height="20">
                <Cell ss:MergeAcross="5" ss:StyleID="Subtitle">
                    <Data ss:Type="String">Philippine College of Science and Technology</Data>
                </Cell>
            </Row>
            <Row ss:Height="8"/>
            <Row ss:Height="22">
                <Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Generated Date</Data></Cell>
                <Cell ss:MergeAcross="4" ss:StyleID="MetaValue"><Data ss:Type="String">{{ $cleanXml($generatedAt) }}</Data></Cell>
            </Row>
            <Row ss:Height="22">
                <Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Department</Data></Cell>
                <Cell ss:MergeAcross="4" ss:StyleID="MetaValue"><Data ss:Type="String">{{ $cleanXml($filters['department'] ?? 'All Departments') }}</Data></Cell>
            </Row>
            <Row ss:Height="22">
                <Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Academic Year / Years</Data></Cell>
                <Cell ss:MergeAcross="4" ss:StyleID="MetaValue"><Data ss:Type="String">{{ $cleanXml($filters['years'] ?? 'All Years') }}</Data></Cell>
            </Row>
            <Row ss:Height="22">
                <Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Matching Records</Data></Cell>
                <Cell ss:MergeAcross="4" ss:StyleID="MetaValue"><Data ss:Type="String">{{ number_format($recordCount) }}</Data></Cell>
            </Row>
            <Row ss:Height="8"/>

            <Row ss:Height="24">
                <Cell ss:StyleID="Header"><Data ss:Type="String">No.</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Title</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Author</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Department</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Program</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Year</Data></Cell>
            </Row>

            @forelse($rows as $row)
                <Row ss:AutoFitHeight="1">
                    <Cell ss:StyleID="CellCenter"><Data ss:Type="Number">{{ (int) $row['#'] }}</Data></Cell>
                    <Cell ss:StyleID="Cell"><Data ss:Type="String">{{ $cleanXml($row['Title']) }}</Data></Cell>
                    <Cell ss:StyleID="Cell"><Data ss:Type="String">{{ $cleanXml($row['Author']) }}</Data></Cell>
                    <Cell ss:StyleID="Cell"><Data ss:Type="String">{{ $cleanXml($row['Department']) }}</Data></Cell>
                    <Cell ss:StyleID="Cell"><Data ss:Type="String">{{ $cleanXml($row['Program']) }}</Data></Cell>
                    <Cell ss:StyleID="CellCenter"><Data ss:Type="Number">{{ (int) $row['Year'] }}</Data></Cell>
                </Row>
            @empty
                <Row ss:Height="28">
                    <Cell ss:MergeAcross="5" ss:StyleID="Empty">
                        <Data ss:Type="String">No matching research records found.</Data>
                    </Cell>
                </Row>
            @endforelse
        </Table>
        <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
            <FreezePanes/>
            <FrozenNoSplit/>
            <SplitHorizontal>9</SplitHorizontal>
            <TopRowBottomPane>9</TopRowBottomPane>
            <ActivePane>2</ActivePane>
            <Panes>
                <Pane>
                    <Number>2</Number>
                    <ActiveRow>9</ActiveRow>
                </Pane>
            </Panes>
            <ProtectObjects>False</ProtectObjects>
            <ProtectScenarios>False</ProtectScenarios>
        </WorksheetOptions>
    </Worksheet>
</Workbook>
