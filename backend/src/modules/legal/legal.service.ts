export interface LegalDocument {
  id: string;
  title: string;
  type: 'contract' | 'agreement' | 'policy' | 'regulation' | 'license' | 'other';
  description: string;
  content: string;
  status: 'draft' | 'review' | 'approved' | 'expired' | 'archived';
  version: string;
  tags: string[];
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
  expiryDate?: Date;
  attachments: string[];
}

export interface LegalCase {
  id: string;
  caseNumber: string;
  title: string;
  description: string;
  type: 'litigation' | 'arbitration' | 'mediation' | 'consultation' | 'other';
  status: 'open' | 'in_progress' | 'pending' | 'closed' | 'settled';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  assignedLawyer: string;
  client: string;
  opponent?: string;
  courtName?: string;
  caseDate: Date;
  nextHearing?: Date;
  documents: string[];
  notes: string;
  outcome?: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface LegalContract {
  id: string;
  contractNumber: string;
  title: string;
  description: string;
  type: 'employment' | 'service' | 'partnership' | 'vendor' | 'nda' | 'other';
  status: 'draft' | 'pending' | 'active' | 'expired' | 'terminated';
  parties: {
    name: string;
    role: 'client' | 'vendor' | 'partner' | 'employee' | 'other';
    contactInfo: string;
  }[];
  startDate: Date;
  endDate?: Date;
  value?: number;
  currency?: string;
  terms: string;
  clauses: string[];
  attachments: string[];
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface ComplianceAudit {
  id: string;
  title: string;
  description: string;
  type: 'internal' | 'external' | 'regulatory' | 'financial' | 'operational';
  status: 'planned' | 'in_progress' | 'completed' | 'failed';
  auditor: string;
  startDate: Date;
  endDate?: Date;
  scope: string[];
  findings: {
    id: string;
    severity: 'low' | 'medium' | 'high' | 'critical';
    description: string;
    recommendation: string;
    status: 'open' | 'in_progress' | 'resolved';
  }[];
  recommendations: string[];
  score?: number;
  createdAt: Date;
  updatedAt: Date;
}

export class LegalService {
  private documents: LegalDocument[] = [];
  private cases: LegalCase[] = [];
  private contracts: LegalContract[] = [];
  private audits: ComplianceAudit[] = [];

  constructor() {
    this.initializeMockData();
  }

  private initializeMockData(): void {
    // Mock documents
    this.documents = [
      {
        id: '1',
        title: 'سياسة الخصوصية',
        type: 'policy',
        description: 'سياسة حماية البيانات الشخصية للعملاء',
        content: 'محتوى سياسة الخصوصية...',
        status: 'approved',
        version: '1.2',
        tags: ['خصوصية', 'بيانات', 'عملاء'],
        createdBy: 'user1',
        createdAt: new Date('2024-01-15'),
        updatedAt: new Date('2024-02-01'),
        attachments: []
      },
      {
        id: '2',
        title: 'رخصة تشغيل',
        type: 'license',
        description: 'رخصة تشغيل الشركة من الجهات المختصة',
        content: 'تفاصيل رخصة التشغيل...',
        status: 'approved',
        version: '1.0',
        tags: ['رخصة', 'تشغيل', 'حكومي'],
        createdBy: 'user2',
        createdAt: new Date('2024-01-01'),
        updatedAt: new Date('2024-01-01'),
        expiryDate: new Date('2025-01-01'),
        attachments: ['license.pdf']
      }
    ];

    // Mock cases
    this.cases = [
      {
        id: '1',
        caseNumber: 'CASE-2024-001',
        title: 'نزاع تجاري مع المورد',
        description: 'نزاع حول جودة البضائع المسلمة',
        type: 'litigation',
        status: 'in_progress',
        priority: 'high',
        assignedLawyer: 'المحامي أحمد محمد',
        client: 'شركة تيسير',
        opponent: 'شركة الموردين المتحدة',
        courtName: 'المحكمة التجارية',
        caseDate: new Date('2024-02-01'),
        nextHearing: new Date('2024-03-15'),
        documents: ['complaint.pdf', 'evidence.pdf'],
        notes: 'تم تقديم الدعوى وننتظر الرد',
        createdAt: new Date('2024-02-01'),
        updatedAt: new Date('2024-02-10')
      }
    ];

    // Mock contracts
    this.contracts = [
      {
        id: '1',
        contractNumber: 'CNT-2024-001',
        title: 'عقد توظيف مطور برمجيات',
        description: 'عقد توظيف لمطور برمجيات أول',
        type: 'employment',
        status: 'active',
        parties: [
          {
            name: 'شركة تيسير',
            role: 'client',
            contactInfo: 'hr@tayseer.com'
          },
          {
            name: 'علي حسن',
            role: 'employee',
            contactInfo: 'ali.hassan@email.com'
          }
        ],
        startDate: new Date('2024-01-01'),
        endDate: new Date('2025-01-01'),
        value: 120000,
        currency: 'SAR',
        terms: 'شروط وأحكام التوظيف...',
        clauses: ['فترة تجريبية 3 أشهر', 'إجازة سنوية 30 يوم'],
        attachments: ['contract.pdf'],
        createdBy: 'hr_manager',
        createdAt: new Date('2023-12-15'),
        updatedAt: new Date('2023-12-15')
      }
    ];

    // Mock audits
    this.audits = [
      {
        id: '1',
        title: 'تدقيق الامتثال السنوي',
        description: 'تدقيق شامل للامتثال للوائح المحلية',
        type: 'regulatory',
        status: 'completed',
        auditor: 'شركة التدقيق المتخصصة',
        startDate: new Date('2024-01-01'),
        endDate: new Date('2024-01-31'),
        scope: ['اللوائح المالية', 'حماية البيانات', 'السلامة المهنية'],
        findings: [
          {
            id: '1',
            severity: 'medium',
            description: 'نقص في توثيق بعض العمليات',
            recommendation: 'تحديث دليل الإجراءات',
            status: 'resolved'
          }
        ],
        recommendations: ['تحسين نظام التوثيق', 'تدريب الموظفين'],
        score: 85,
        createdAt: new Date('2024-01-01'),
        updatedAt: new Date('2024-02-01')
      }
    ];
  }

  // Document methods
  async getAllDocuments(page: number = 1, limit: number = 10, filters: any = {}): Promise<{
    documents: LegalDocument[];
    total: number;
    page: number;
    totalPages: number;
  }> {
    let filteredDocuments = [...this.documents];

    if (filters.type) {
      filteredDocuments = filteredDocuments.filter(doc => doc.type === filters.type);
    }

    if (filters.status) {
      filteredDocuments = filteredDocuments.filter(doc => doc.status === filters.status);
    }

    const total = filteredDocuments.length;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    const documents = filteredDocuments.slice(startIndex, endIndex);

    return {
      documents,
      total,
      page,
      totalPages: Math.ceil(total / limit)
    };
  }

  async getDocumentById(id: string): Promise<LegalDocument | null> {
    return this.documents.find(doc => doc.id === id) || null;
  }

  async createDocument(documentData: Partial<LegalDocument>): Promise<LegalDocument> {
    const newDocument: LegalDocument = {
      id: (this.documents.length + 1).toString(),
      title: documentData.title || '',
      type: documentData.type || 'other',
      description: documentData.description || '',
      content: documentData.content || '',
      status: documentData.status || 'draft',
      version: documentData.version || '1.0',
      tags: documentData.tags || [],
      createdBy: documentData.createdBy || 'system',
      createdAt: new Date(),
      updatedAt: new Date(),
      expiryDate: documentData.expiryDate,
      attachments: documentData.attachments || []
    };

    this.documents.push(newDocument);
    return newDocument;
  }

  async updateDocument(id: string, updateData: Partial<LegalDocument>): Promise<LegalDocument | null> {
    const index = this.documents.findIndex(doc => doc.id === id);
    if (index === -1) return null;

    this.documents[index] = {
      ...this.documents[index],
      ...updateData,
      updatedAt: new Date()
    };

    return this.documents[index];
  }

  async deleteDocument(id: string): Promise<boolean> {
    const index = this.documents.findIndex(doc => doc.id === id);
    if (index === -1) return false;

    this.documents.splice(index, 1);
    return true;
  }

  // Case methods
  async getAllCases(page: number = 1, limit: number = 10, filters: any = {}): Promise<{
    cases: LegalCase[];
    total: number;
    page: number;
    totalPages: number;
  }> {
    let filteredCases = [...this.cases];

    if (filters.status) {
      filteredCases = filteredCases.filter(c => c.status === filters.status);
    }

    if (filters.type) {
      filteredCases = filteredCases.filter(c => c.type === filters.type);
    }

    const total = filteredCases.length;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    const cases = filteredCases.slice(startIndex, endIndex);

    return {
      cases,
      total,
      page,
      totalPages: Math.ceil(total / limit)
    };
  }

  async getCaseById(id: string): Promise<LegalCase | null> {
    return this.cases.find(c => c.id === id) || null;
  }

  async createCase(caseData: Partial<LegalCase>): Promise<LegalCase> {
    const newCase: LegalCase = {
      id: (this.cases.length + 1).toString(),
      caseNumber: caseData.caseNumber || `CASE-${new Date().getFullYear()}-${(this.cases.length + 1).toString().padStart(3, '0')}`,
      title: caseData.title || '',
      description: caseData.description || '',
      type: caseData.type || 'other',
      status: caseData.status || 'open',
      priority: caseData.priority || 'medium',
      assignedLawyer: caseData.assignedLawyer || '',
      client: caseData.client || '',
      opponent: caseData.opponent,
      courtName: caseData.courtName,
      caseDate: caseData.caseDate || new Date(),
      nextHearing: caseData.nextHearing,
      documents: caseData.documents || [],
      notes: caseData.notes || '',
      outcome: caseData.outcome,
      createdAt: new Date(),
      updatedAt: new Date()
    };

    this.cases.push(newCase);
    return newCase;
  }

  async updateCase(id: string, updateData: Partial<LegalCase>): Promise<LegalCase | null> {
    const index = this.cases.findIndex(c => c.id === id);
    if (index === -1) return null;

    this.cases[index] = {
      ...this.cases[index],
      ...updateData,
      updatedAt: new Date()
    };

    return this.cases[index];
  }

  async deleteCase(id: string): Promise<boolean> {
    const index = this.cases.findIndex(c => c.id === id);
    if (index === -1) return false;

    this.cases.splice(index, 1);
    return true;
  }

  // Contract methods
  async getAllContracts(page: number = 1, limit: number = 10, filters: any = {}): Promise<{
    contracts: LegalContract[];
    total: number;
    page: number;
    totalPages: number;
  }> {
    let filteredContracts = [...this.contracts];

    if (filters.status) {
      filteredContracts = filteredContracts.filter(c => c.status === filters.status);
    }

    if (filters.type) {
      filteredContracts = filteredContracts.filter(c => c.type === filters.type);
    }

    const total = filteredContracts.length;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    const contracts = filteredContracts.slice(startIndex, endIndex);

    return {
      contracts,
      total,
      page,
      totalPages: Math.ceil(total / limit)
    };
  }

  async getContractById(id: string): Promise<LegalContract | null> {
    return this.contracts.find(c => c.id === id) || null;
  }

  async createContract(contractData: Partial<LegalContract>): Promise<LegalContract> {
    const newContract: LegalContract = {
      id: (this.contracts.length + 1).toString(),
      contractNumber: contractData.contractNumber || `CNT-${new Date().getFullYear()}-${(this.contracts.length + 1).toString().padStart(3, '0')}`,
      title: contractData.title || '',
      description: contractData.description || '',
      type: contractData.type || 'other',
      status: contractData.status || 'draft',
      parties: contractData.parties || [],
      startDate: contractData.startDate || new Date(),
      endDate: contractData.endDate,
      value: contractData.value,
      currency: contractData.currency,
      terms: contractData.terms || '',
      clauses: contractData.clauses || [],
      attachments: contractData.attachments || [],
      createdBy: contractData.createdBy || 'system',
      createdAt: new Date(),
      updatedAt: new Date()
    };

    this.contracts.push(newContract);
    return newContract;
  }

  async updateContract(id: string, updateData: Partial<LegalContract>): Promise<LegalContract | null> {
    const index = this.contracts.findIndex(c => c.id === id);
    if (index === -1) return null;

    this.contracts[index] = {
      ...this.contracts[index],
      ...updateData,
      updatedAt: new Date()
    };

    return this.contracts[index];
  }

  async deleteContract(id: string): Promise<boolean> {
    const index = this.contracts.findIndex(c => c.id === id);
    if (index === -1) return false;

    this.contracts.splice(index, 1);
    return true;
  }

  // Compliance methods
  async getComplianceStatus(): Promise<{
    overallScore: number;
    lastAuditDate: Date;
    nextAuditDue: Date;
    openFindings: number;
    riskLevel: 'low' | 'medium' | 'high';
    areas: {
      name: string;
      score: number;
      status: 'compliant' | 'non_compliant' | 'pending';
    }[];
  }> {
    const lastAudit = this.audits
      .filter(a => a.status === 'completed')
      .sort((a, b) => new Date(b.endDate || b.createdAt).getTime() - new Date(a.endDate || a.createdAt).getTime())[0];

    const openFindings = this.audits
      .flatMap(a => a.findings)
      .filter(f => f.status !== 'resolved').length;

    const overallScore = lastAudit?.score || 0;
    let riskLevel: 'low' | 'medium' | 'high' = 'low';
    
    if (overallScore < 60) riskLevel = 'high';
    else if (overallScore < 80) riskLevel = 'medium';

    return {
      overallScore,
      lastAuditDate: lastAudit?.endDate || new Date(),
      nextAuditDue: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000), // Next year
      openFindings,
      riskLevel,
      areas: [
        { name: 'اللوائح المالية', score: 90, status: 'compliant' },
        { name: 'حماية البيانات', score: 85, status: 'compliant' },
        { name: 'السلامة المهنية', score: 75, status: 'pending' },
        { name: 'البيئة والاستدامة', score: 80, status: 'compliant' }
      ]
    };
  }

  async createComplianceAudit(auditData: Partial<ComplianceAudit>): Promise<ComplianceAudit> {
    const newAudit: ComplianceAudit = {
      id: (this.audits.length + 1).toString(),
      title: auditData.title || '',
      description: auditData.description || '',
      type: auditData.type || 'internal',
      status: auditData.status || 'planned',
      auditor: auditData.auditor || '',
      startDate: auditData.startDate || new Date(),
      endDate: auditData.endDate,
      scope: auditData.scope || [],
      findings: auditData.findings || [],
      recommendations: auditData.recommendations || [],
      score: auditData.score,
      createdAt: new Date(),
      updatedAt: new Date()
    };

    this.audits.push(newAudit);
    return newAudit;
  }

  async getComplianceReports(startDate?: Date, endDate?: Date): Promise<{
    reports: ComplianceAudit[];
    summary: {
      totalAudits: number;
      completedAudits: number;
      averageScore: number;
      criticalFindings: number;
    };
  }> {
    let filteredAudits = [...this.audits];

    if (startDate) {
      filteredAudits = filteredAudits.filter(a => new Date(a.createdAt) >= startDate);
    }

    if (endDate) {
      filteredAudits = filteredAudits.filter(a => new Date(a.createdAt) <= endDate);
    }

    const completedAudits = filteredAudits.filter(a => a.status === 'completed');
    const averageScore = completedAudits.reduce((sum, a) => sum + (a.score || 0), 0) / completedAudits.length || 0;
    const criticalFindings = filteredAudits
      .flatMap(a => a.findings)
      .filter(f => f.severity === 'critical').length;

    return {
      reports: filteredAudits,
      summary: {
        totalAudits: filteredAudits.length,
        completedAudits: completedAudits.length,
        averageScore,
        criticalFindings
      }
    };
  }
}