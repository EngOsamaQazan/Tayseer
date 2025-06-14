import { Request } from 'express';
import logger from '../../config/logger';

// Types and interfaces
interface SupportTicket {
  id: string;
  title: string;
  description: string;
  status: 'open' | 'in-progress' | 'resolved' | 'closed' | 'escalated';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  category: string;
  customerId: string;
  assignedTo?: string;
  assignedBy?: string;
  createdAt: Date;
  updatedAt: Date;
  resolvedAt?: Date;
  closedAt?: Date;
  escalatedAt?: Date;
  escalationLevel?: number;
  escalationReason?: string;
  attachments?: string[];
  tags?: string[];
  customerFeedback?: {
    rating: number;
    comment: string;
    submittedAt: Date;
  };
}

interface TicketResponse {
  id: string;
  ticketId: string;
  message: string;
  isInternal: boolean;
  authorId: string;
  authorType: 'customer' | 'support';
  createdAt: Date;
  attachments?: string[];
}

interface KnowledgeBase {
  id: string;
  title: string;
  content: string;
  category: string;
  tags: string[];
  isPublic: boolean;
  views: number;
  helpful: number;
  notHelpful: number;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
}

interface FAQ {
  id: string;
  question: string;
  answer: string;
  category: string;
  tags: string[];
  isPublic: boolean;
  views: number;
  helpful: number;
  notHelpful: number;
  order: number;
  createdAt: Date;
  updatedAt: Date;
}

interface SupportTeamMember {
  id: string;
  name: string;
  email: string;
  role: string;
  department: string;
  skills: string[];
  languages: string[];
  isOnline: boolean;
  workload: number;
  maxTickets: number;
  averageResponseTime: number;
  customerSatisfactionRating: number;
  totalTicketsResolved: number;
  createdAt: Date;
}

export class SupportService {
  // Mock data for demonstration
  private tickets: SupportTicket[] = [
    {
      id: '1',
      title: 'مشكلة في تسجيل الدخول',
      description: 'لا أستطيع الدخول إلى حسابي',
      status: 'open',
      priority: 'high',
      category: 'authentication',
      customerId: 'customer1',
      assignedTo: 'support1',
      createdAt: new Date('2024-01-01'),
      updatedAt: new Date('2024-01-01')
    },
    {
      id: '2',
      title: 'استفسار عن الفاتورة',
      description: 'لدي سؤال حول الفاتورة الأخيرة',
      status: 'resolved',
      priority: 'medium',
      category: 'billing',
      customerId: 'customer2',
      assignedTo: 'support2',
      createdAt: new Date('2024-01-02'),
      updatedAt: new Date('2024-01-03'),
      resolvedAt: new Date('2024-01-03'),
      customerFeedback: {
        rating: 5,
        comment: 'خدمة ممتازة وسريعة',
        submittedAt: new Date('2024-01-03')
      }
    }
  ];

  private responses: TicketResponse[] = [
    {
      id: '1',
      ticketId: '1',
      message: 'شكراً لتواصلك معنا. سنراجع مشكلتك قريباً.',
      isInternal: false,
      authorId: 'support1',
      authorType: 'support',
      createdAt: new Date('2024-01-01')
    }
  ];

  private knowledgeBase: KnowledgeBase[] = [
    {
      id: '1',
      title: 'كيفية إعادة تعيين كلمة المرور',
      content: 'لإعادة تعيين كلمة المرور، اتبع الخطوات التالية...',
      category: 'authentication',
      tags: ['password', 'reset', 'login'],
      isPublic: true,
      views: 150,
      helpful: 45,
      notHelpful: 3,
      createdBy: 'admin1',
      createdAt: new Date('2024-01-01'),
      updatedAt: new Date('2024-01-01')
    }
  ];

  private faqs: FAQ[] = [
    {
      id: '1',
      question: 'كيف يمكنني تغيير كلمة المرور؟',
      answer: 'يمكنك تغيير كلمة المرور من خلال الذهاب إلى الإعدادات...',
      category: 'account',
      tags: ['password', 'account'],
      isPublic: true,
      views: 200,
      helpful: 85,
      notHelpful: 5,
      order: 1,
      createdAt: new Date('2024-01-01'),
      updatedAt: new Date('2024-01-01')
    }
  ];

  private supportTeam: SupportTeamMember[] = [
    {
      id: 'support1',
      name: 'أحمد محمد',
      email: 'ahmed@company.com',
      role: 'Senior Support Specialist',
      department: 'Technical Support',
      skills: ['troubleshooting', 'customer_service', 'arabic', 'english'],
      languages: ['ar', 'en'],
      isOnline: true,
      workload: 15,
      maxTickets: 20,
      averageResponseTime: 30,
      customerSatisfactionRating: 4.8,
      totalTicketsResolved: 1250,
      createdAt: new Date('2024-01-01')
    }
  ];

  // Ticket management
  async getTickets(query: any): Promise<{ tickets: SupportTicket[]; total: number; page: number; limit: number }> {
    logger.info('جلب قائمة التذاكر');
    
    let filteredTickets = [...this.tickets];
    
    // Apply filters
    if (query.status) {
      filteredTickets = filteredTickets.filter(ticket => ticket.status === query.status);
    }
    
    if (query.priority) {
      filteredTickets = filteredTickets.filter(ticket => ticket.priority === query.priority);
    }
    
    if (query.category) {
      filteredTickets = filteredTickets.filter(ticket => ticket.category === query.category);
    }
    
    if (query.assignedTo) {
      filteredTickets = filteredTickets.filter(ticket => ticket.assignedTo === query.assignedTo);
    }
    
    if (query.customerId) {
      filteredTickets = filteredTickets.filter(ticket => ticket.customerId === query.customerId);
    }
    
    // Pagination
    const page = parseInt(query.page) || 1;
    const limit = parseInt(query.limit) || 10;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    
    const paginatedArticles = filteredArticles.slice(startIndex, endIndex);
    
    return {
      articles: paginatedArticles,
      total: filteredArticles.length,
      page,
      limit
    };
  }

  async createKnowledgeBaseArticle(articleData: Partial<KnowledgeBase>): Promise<KnowledgeBase> {
    logger.info('إنشاء مقال جديد في قاعدة المعرفة');
    
    const newArticle: KnowledgeBase = {
      id: (this.knowledgeBase.length + 1).toString(),
      title: articleData.title || '',
      content: articleData.content || '',
      category: articleData.category || 'general',
      tags: articleData.tags || [],
      isPublic: articleData.isPublic || false,
      views: 0,
      helpful: 0,
      notHelpful: 0,
      createdBy: articleData.createdBy || '',
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    this.knowledgeBase.push(newArticle);
    return newArticle;
  }

  async getKnowledgeBaseArticle(id: string): Promise<KnowledgeBase | null> {
    logger.info(`جلب مقال قاعدة المعرفة: ${id}`);
    const article = this.knowledgeBase.find(article => article.id === id);
    
    if (article) {
      // Increment view count
      article.views++;
    }
    
    return article || null;
  }

  async updateKnowledgeBaseArticle(id: string, updateData: Partial<KnowledgeBase>): Promise<KnowledgeBase | null> {
    logger.info(`تحديث مقال قاعدة المعرفة: ${id}`);
    
    const articleIndex = this.knowledgeBase.findIndex(article => article.id === id);
    if (articleIndex === -1) return null;
    
    this.knowledgeBase[articleIndex] = {
      ...this.knowledgeBase[articleIndex],
      ...updateData,
      updatedAt: new Date()
    };
    
    return this.knowledgeBase[articleIndex];
  }

  async deleteKnowledgeBaseArticle(id: string): Promise<boolean> {
    logger.info(`حذف مقال قاعدة المعرفة: ${id}`);
    
    const articleIndex = this.knowledgeBase.findIndex(article => article.id === id);
    if (articleIndex === -1) return false;
    
    this.knowledgeBase.splice(articleIndex, 1);
    return true;
  }

  async rateKnowledgeBaseArticle(id: string, isHelpful: boolean): Promise<KnowledgeBase | null> {
    logger.info(`تقييم مقال قاعدة المعرفة: ${id}`);
    
    const article = this.knowledgeBase.find(article => article.id === id);
    if (!article) return null;
    
    if (isHelpful) {
      article.helpful++;
    } else {
      article.notHelpful++;
    }
    
    return article;
  }

  // FAQ management
  async getFAQs(query: any): Promise<{ faqs: FAQ[]; total: number; page: number; limit: number }> {
    logger.info('جلب الأسئلة الشائعة');
    
    let filteredFAQs = [...this.faqs];
    
    if (query.category) {
      filteredFAQs = filteredFAQs.filter(faq => faq.category === query.category);
    }
    
    if (query.isPublic !== undefined) {
      filteredFAQs = filteredFAQs.filter(faq => faq.isPublic === (query.isPublic === 'true'));
    }
    
    if (query.search) {
      const searchTerm = query.search.toLowerCase();
      filteredFAQs = filteredFAQs.filter(faq => 
        faq.question.toLowerCase().includes(searchTerm) ||
        faq.answer.toLowerCase().includes(searchTerm) ||
        faq.tags.some(tag => tag.toLowerCase().includes(searchTerm))
      );
    }
    
    // Sort by order
    filteredFAQs.sort((a, b) => a.order - b.order);
    
    // Pagination
    const page = parseInt(query.page) || 1;
    const limit = parseInt(query.limit) || 10;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    
    const paginatedFAQs = filteredFAQs.slice(startIndex, endIndex);
    
    return {
      faqs: paginatedFAQs,
      total: filteredFAQs.length,
      page,
      limit
    };
  }

  async createFAQ(faqData: Partial<FAQ>): Promise<FAQ> {
    logger.info('إنشاء سؤال شائع جديد');
    
    const newFAQ: FAQ = {
      id: (this.faqs.length + 1).toString(),
      question: faqData.question || '',
      answer: faqData.answer || '',
      category: faqData.category || 'general',
      tags: faqData.tags || [],
      isPublic: faqData.isPublic || false,
      views: 0,
      helpful: 0,
      notHelpful: 0,
      order: faqData.order || this.faqs.length + 1,
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    this.faqs.push(newFAQ);
    return newFAQ;
  }

  async getFAQ(id: string): Promise<FAQ | null> {
    logger.info(`جلب السؤال الشائع: ${id}`);
    const faq = this.faqs.find(faq => faq.id === id);
    
    if (faq) {
      // Increment view count
      faq.views++;
    }
    
    return faq || null;
  }

  async updateFAQ(id: string, updateData: Partial<FAQ>): Promise<FAQ | null> {
    logger.info(`تحديث السؤال الشائع: ${id}`);
    
    const faqIndex = this.faqs.findIndex(faq => faq.id === id);
    if (faqIndex === -1) return null;
    
    this.faqs[faqIndex] = {
      ...this.faqs[faqIndex],
      ...updateData,
      updatedAt: new Date()
    };
    
    return this.faqs[faqIndex];
  }

  async deleteFAQ(id: string): Promise<boolean> {
    logger.info(`حذف السؤال الشائع: ${id}`);
    
    const faqIndex = this.faqs.findIndex(faq => faq.id === id);
    if (faqIndex === -1) return false;
    
    this.faqs.splice(faqIndex, 1);
    return true;
  }

  async rateFAQ(id: string, isHelpful: boolean): Promise<FAQ | null> {
    logger.info(`تقييم السؤال الشائع: ${id}`);
    
    const faq = this.faqs.find(faq => faq.id === id);
    if (!faq) return null;
    
    if (isHelpful) {
      faq.helpful++;
    } else {
      faq.notHelpful++;
    }
    
    return faq;
  }

  // Analytics and reporting
  async getTicketStats(period: string = '30d'): Promise<any> {
    logger.info(`جلب إحصائيات التذاكر للفترة: ${period}`);
    
    const stats = {
      totalTickets: this.tickets.length,
      openTickets: this.tickets.filter(t => t.status === 'open').length,
      inProgressTickets: this.tickets.filter(t => t.status === 'in-progress').length,
      resolvedTickets: this.tickets.filter(t => t.status === 'resolved').length,
      closedTickets: this.tickets.filter(t => t.status === 'closed').length,
      escalatedTickets: this.tickets.filter(t => t.status === 'escalated').length,
      priorityDistribution: {
        low: this.tickets.filter(t => t.priority === 'low').length,
        medium: this.tickets.filter(t => t.priority === 'medium').length,
        high: this.tickets.filter(t => t.priority === 'high').length,
        urgent: this.tickets.filter(t => t.priority === 'urgent').length
      },
      categoryDistribution: this.getCategoryDistribution(),
      averageResolutionTime: this.calculateAverageResolutionTime(),
      customerSatisfactionRating: this.calculateCustomerSatisfactionRating()
    };
    
    return stats;
  }

  async getSupportPerformance(period: string = '30d'): Promise<any> {
    logger.info(`جلب أداء الدعم للفترة: ${period}`);
    
    return {
      totalAgents: this.supportTeam.length,
      onlineAgents: this.supportTeam.filter(agent => agent.isOnline).length,
      averageResponseTime: this.calculateAverageResponseTime(),
      ticketsPerAgent: this.calculateTicketsPerAgent(),
      customerSatisfactionByAgent: this.calculateSatisfactionByAgent(),
      resolutionRate: this.calculateResolutionRate()
    };
  }

  async getTicketReports(query: any): Promise<any> {
    logger.info('جلب تقارير التذاكر');
    
    return {
      dailyTicketCreation: this.getDailyTicketCreation(),
      weeklyResolutionStats: this.getWeeklyResolutionStats(),
      monthlyTrends: this.getMonthlyTrends(),
      agentPerformance: this.getAgentPerformanceReport()
    };
  }

  async getCustomerSatisfactionReports(period: string = '30d'): Promise<any> {
    logger.info(`جلب تقارير رضا العملاء للفترة: ${period}`);
    
    const ticketsWithFeedback = this.tickets.filter(t => t.customerFeedback);
    
    return {
      averageRating: this.calculateAverageRating(ticketsWithFeedback),
      ratingDistribution: this.getRatingDistribution(ticketsWithFeedback),
      feedbackComments: ticketsWithFeedback.map(t => ({
        ticketId: t.id,
        rating: t.customerFeedback?.rating,
        comment: t.customerFeedback?.comment,
        submittedAt: t.customerFeedback?.submittedAt
      })),
      improvementAreas: this.identifyImprovementAreas(ticketsWithFeedback)
    };
  }

  // Customer feedback
  async addCustomerFeedback(ticketId: string, feedback: { rating: number; comment: string }): Promise<SupportTicket | null> {
    logger.info(`إضافة تقييم العميل للتذكرة: ${ticketId}`);
    
    const ticket = this.tickets.find(t => t.id === ticketId);
    if (!ticket) return null;
    
    ticket.customerFeedback = {
      rating: feedback.rating,
      comment: feedback.comment,
      submittedAt: new Date()
    };
    
    return ticket;
  }

  async getCustomerFeedback(query: any): Promise<{ feedback: any[]; total: number; page: number; limit: number }> {
    logger.info('جلب تقييمات العملاء');
    
    const ticketsWithFeedback = this.tickets.filter(t => t.customerFeedback);
    
    let filteredFeedback = ticketsWithFeedback.map(t => ({
      ticketId: t.id,
      ticketTitle: t.title,
      customerId: t.customerId,
      assignedTo: t.assignedTo,
      rating: t.customerFeedback?.rating,
      comment: t.customerFeedback?.comment,
      submittedAt: t.customerFeedback?.submittedAt
    }));
    
    if (query.rating) {
      filteredFeedback = filteredFeedback.filter(f => f.rating === parseInt(query.rating));
    }
    
    if (query.assignedTo) {
      filteredFeedback = filteredFeedback.filter(f => f.assignedTo === query.assignedTo);
    }
    
    // Pagination
    const page = parseInt(query.page) || 1;
    const limit = parseInt(query.limit) || 10;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    
    const paginatedFeedback = filteredFeedback.slice(startIndex, endIndex);
    
    return {
      feedback: paginatedFeedback,
      total: filteredFeedback.length,
      page,
      limit
    };
  }

  // Support team management
  async getSupportTeam(): Promise<SupportTeamMember[]> {
    logger.info('جلب فريق الدعم');
    return this.supportTeam;
  }

  async getTeamMemberPerformance(memberId: string, period: string = '30d'): Promise<any> {
    logger.info(`جلب أداء عضو الفريق: ${memberId} للفترة: ${period}`);
    
    const member = this.supportTeam.find(m => m.id === memberId);
    if (!member) return null;
    
    const memberTickets = this.tickets.filter(t => t.assignedTo === memberId);
    
    return {
      member: {
        id: member.id,
        name: member.name,
        email: member.email,
        role: member.role,
        department: member.department
      },
      stats: {
        totalTickets: memberTickets.length,
        resolvedTickets: memberTickets.filter(t => t.status === 'resolved').length,
        averageResolutionTime: this.calculateMemberAverageResolutionTime(memberId),
        customerSatisfactionRating: this.calculateMemberSatisfactionRating(memberId),
        responseTime: this.calculateMemberResponseTime(memberId)
      }
    };
  }

  // Escalation management
  async escalateTicket(ticketId: string, escalationData: { reason: string; escalatedTo: string }): Promise<SupportTicket | null> {
    logger.info(`تصعيد التذكرة: ${ticketId}`);
    
    const ticket = this.tickets.find(t => t.id === ticketId);
    if (!ticket) return null;
    
    ticket.status = 'escalated';
    ticket.escalationHistory = ticket.escalationHistory || [];
    ticket.escalationHistory.push({
      escalatedAt: new Date(),
      escalatedBy: ticket.assignedTo || '',
      escalatedTo: escalationData.escalatedTo,
      reason: escalationData.reason
    });
    
    // Assign to escalated agent
    ticket.assignedTo = escalationData.escalatedTo;
    
    return ticket;
  }

  async getEscalatedTickets(query: any): Promise<{ tickets: SupportTicket[]; total: number; page: number; limit: number }> {
    logger.info('جلب التذاكر المصعدة');
    
    let escalatedTickets = this.tickets.filter(t => t.status === 'escalated');
    
    if (query.assignedTo) {
      escalatedTickets = escalatedTickets.filter(t => t.assignedTo === query.assignedTo);
    }
    
    if (query.priority) {
      escalatedTickets = escalatedTickets.filter(t => t.priority === query.priority);
    }
    
    // Sort by creation date (newest first)
    escalatedTickets.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());
    
    // Pagination
    const page = parseInt(query.page) || 1;
    const limit = parseInt(query.limit) || 10;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    
    const paginatedTickets = escalatedTickets.slice(startIndex, endIndex);
    
    return {
      tickets: paginatedTickets,
      total: escalatedTickets.length,
      page,
      limit
    };
  }

  // Helper methods for calculations
  private getCategoryDistribution(): { [key: string]: number } {
    const distribution: { [key: string]: number } = {};
    
    this.tickets.forEach(ticket => {
      const category = ticket.category || 'uncategorized';
      distribution[category] = (distribution[category] || 0) + 1;
    });
    
    return distribution;
  }

  private calculateAverageResolutionTime(): number {
    const resolvedTickets = this.tickets.filter(t => t.status === 'resolved' && t.resolvedAt);
    if (resolvedTickets.length === 0) return 0;
    
    const totalTime = resolvedTickets.reduce((acc, ticket) => {
      const resolutionTime = ticket.resolvedAt!.getTime() - ticket.createdAt.getTime();
      return acc + resolutionTime;
    }, 0);
    
    return totalTime / resolvedTickets.length / (1000 * 60 * 60); // Convert to hours
  }

  private calculateCustomerSatisfactionRating(): number {
    const ticketsWithRating = this.tickets.filter(t => t.customerFeedback?.rating);
    if (ticketsWithRating.length === 0) return 0;
    
    const totalRating = ticketsWithRating.reduce((acc, ticket) => {
      return acc + (ticket.customerFeedback?.rating || 0);
    }, 0);
    
    return totalRating / ticketsWithRating.length;
  }

  private calculateAverageResponseTime(): number {
    // Mock calculation - in real implementation, this would calculate time to first response
    return 2.5; // hours
  }

  private calculateTicketsPerAgent(): { [key: string]: number } {
    const ticketsPerAgent: { [key: string]: number } = {};
    
    this.tickets.forEach(ticket => {
      const agent = ticket.assignedTo || 'unassigned';
      ticketsPerAgent[agent] = (ticketsPerAgent[agent] || 0) + 1;
    });
    
    return ticketsPerAgent;
  }

  private calculateSatisfactionByAgent(): { [key: string]: number } {
    const satisfactionByAgent: { [key: string]: { total: number; count: number } } = {};
    
    this.tickets
      .filter(t => t.assignedTo && t.customerFeedback?.rating)
      .forEach(ticket => {
        const agent = ticket.assignedTo!;
        const rating = ticket.customerFeedback!.rating!;
        
        if (!satisfactionByAgent[agent]) {
          satisfactionByAgent[agent] = { total: 0, count: 0 };
        }
        
        satisfactionByAgent[agent].total += rating;
        satisfactionByAgent[agent].count += 1;
      });
    
    const result: { [key: string]: number } = {};
    Object.keys(satisfactionByAgent).forEach(agent => {
      result[agent] = satisfactionByAgent[agent].total / satisfactionByAgent[agent].count;
    });
    
    return result;
  }

  private calculateResolutionRate(): number {
    if (this.tickets.length === 0) return 0;
    
    const resolvedTickets = this.tickets.filter(t => t.status === 'resolved' || t.status === 'closed');
    return (resolvedTickets.length / this.tickets.length) * 100;
  }

  private getDailyTicketCreation(): any[] {
    // Mock data - in real implementation, this would aggregate by date
    return [
      { date: '2024-01-01', count: 5 },
      { date: '2024-01-02', count: 8 },
      { date: '2024-01-03', count: 3 }
    ];
  }

  private getWeeklyResolutionStats(): any[] {
    // Mock data
    return [
      { week: '2024-W01', resolved: 25, total: 30 },
      { week: '2024-W02', resolved: 22, total: 28 }
    ];
  }

  private getMonthlyTrends(): any[] {
    // Mock data
    return [
      { month: '2024-01', tickets: 120, resolved: 110, satisfaction: 4.2 },
      { month: '2024-02', tickets: 135, resolved: 125, satisfaction: 4.4 }
    ];
  }

  private getAgentPerformanceReport(): any[] {
    return this.supportTeam.map(agent => ({
      agentId: agent.id,
      name: agent.name,
      ticketsAssigned: this.tickets.filter(t => t.assignedTo === agent.id).length,
      ticketsResolved: this.tickets.filter(t => t.assignedTo === agent.id && t.status === 'resolved').length,
      averageRating: this.calculateMemberSatisfactionRating(agent.id),
      responseTime: this.calculateMemberResponseTime(agent.id)
    }));
  }

  private calculateAverageRating(tickets: SupportTicket[]): number {
    if (tickets.length === 0) return 0;
    
    const totalRating = tickets.reduce((acc, ticket) => {
      return acc + (ticket.customerFeedback?.rating || 0);
    }, 0);
    
    return totalRating / tickets.length;
  }

  private getRatingDistribution(tickets: SupportTicket[]): { [key: number]: number } {
    const distribution: { [key: number]: number } = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };
    
    tickets.forEach(ticket => {
      const rating = ticket.customerFeedback?.rating;
      if (rating && rating >= 1 && rating <= 5) {
        distribution[rating]++;
      }
    });
    
    return distribution;
  }

  private identifyImprovementAreas(tickets: SupportTicket[]): string[] {
    // Simple analysis based on low ratings and common complaint patterns
    const lowRatingTickets = tickets.filter(t => (t.customerFeedback?.rating || 0) <= 2);
    const improvementAreas: string[] = [];
    
    if (lowRatingTickets.length > tickets.length * 0.2) {
      improvementAreas.push('تحسين وقت الاستجابة');
    }
    
    const categoryIssues = this.getCategoryDistribution();
    const maxIssueCategory = Object.keys(categoryIssues).reduce((a, b) => 
      categoryIssues[a] > categoryIssues[b] ? a : b
    );
    
    if (categoryIssues[maxIssueCategory] > tickets.length * 0.3) {
      improvementAreas.push(`تحسين الدعم في فئة: ${maxIssueCategory}`);
    }
    
    return improvementAreas;
  }

  private calculateMemberAverageResolutionTime(memberId: string): number {
    const memberTickets = this.tickets.filter(t => 
      t.assignedTo === memberId && t.status === 'resolved' && t.resolvedAt
    );
    
    if (memberTickets.length === 0) return 0;
    
    const totalTime = memberTickets.reduce((acc, ticket) => {
      const resolutionTime = ticket.resolvedAt!.getTime() - ticket.createdAt.getTime();
      return acc + resolutionTime;
    }, 0);
    
    return totalTime / memberTickets.length / (1000 * 60 * 60); // Convert to hours
  }

  private calculateMemberSatisfactionRating(memberId: string): number {
    const memberTickets = this.tickets.filter(t => 
      t.assignedTo === memberId && t.customerFeedback?.rating
    );
    
    if (memberTickets.length === 0) return 0;
    
    const totalRating = memberTickets.reduce((acc, ticket) => {
      return acc + (ticket.customerFeedback?.rating || 0);
    }, 0);
    
    return totalRating / memberTickets.length;
  }

  private calculateMemberResponseTime(memberId: string): number {
    // Mock calculation - in real implementation, this would calculate actual response times
    return Math.random() * 5 + 1; // Random value between 1-6 hours
  }

  // Additional method for handling ticket pagination
  private paginateTickets(tickets: SupportTicket[], page: number, limit: number): any {
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    
    const paginatedTickets = tickets.slice(startIndex, endIndex);
    
    return {
      tickets: paginatedTickets,
      total: tickets.length,
      page,
      limit
    };
  }

  async createTicket(ticketData: Partial<SupportTicket>): Promise<SupportTicket> {
    logger.info('إنشاء تذكرة جديدة');
    
    const newTicket: SupportTicket = {
      id: (this.tickets.length + 1).toString(),
      title: ticketData.title || '',
      description: ticketData.description || '',
      status: 'open',
      priority: ticketData.priority || 'medium',
      category: ticketData.category || 'general',
      customerId: ticketData.customerId || '',
      assignedTo: ticketData.assignedTo,
      assignedBy: ticketData.assignedBy,
      createdAt: new Date(),
      updatedAt: new Date(),
      attachments: ticketData.attachments || [],
      tags: ticketData.tags || []
    };
    
    this.tickets.push(newTicket);
    return newTicket;
  }

  async getTicketById(id: string): Promise<SupportTicket | null> {
    logger.info(`جلب التذكرة بالمعرف: ${id}`);
    return this.tickets.find(ticket => ticket.id === id) || null;
  }

  async updateTicket(id: string, updateData: Partial<SupportTicket>): Promise<SupportTicket | null> {
    logger.info(`تحديث التذكرة: ${id}`);
    
    const ticketIndex = this.tickets.findIndex(ticket => ticket.id === id);
    if (ticketIndex === -1) return null;
    
    this.tickets[ticketIndex] = {
      ...this.tickets[ticketIndex],
      ...updateData,
      updatedAt: new Date()
    };
    
    return this.tickets[ticketIndex];
  }

  async deleteTicket(id: string): Promise<boolean> {
    logger.info(`حذف التذكرة: ${id}`);
    
    const ticketIndex = this.tickets.findIndex(ticket => ticket.id === id);
    if (ticketIndex === -1) return false;
    
    this.tickets.splice(ticketIndex, 1);
    return true;
  }

  async updateTicketStatus(id: string, status: SupportTicket['status'], userId: string): Promise<SupportTicket | null> {
    logger.info(`تحديث حالة التذكرة ${id} إلى ${status}`);
    
    const ticket = await this.getTicketById(id);
    if (!ticket) return null;
    
    const updateData: Partial<SupportTicket> = {
      status,
      updatedAt: new Date()
    };
    
    if (status === 'resolved') {
      updateData.resolvedAt = new Date();
    } else if (status === 'closed') {
      updateData.closedAt = new Date();
    }
    
    return this.updateTicket(id, updateData);
  }

  async assignTicket(id: string, assignedTo: string, assignedBy: string): Promise<SupportTicket | null> {
    logger.info(`تعيين التذكرة ${id} إلى ${assignedTo}`);
    
    return this.updateTicket(id, {
      assignedTo,
      assignedBy,
      status: 'in-progress',
      updatedAt: new Date()
    });
  }

  async updateTicketPriority(id: string, priority: SupportTicket['priority']): Promise<SupportTicket | null> {
    logger.info(`تحديث أولوية التذكرة ${id} إلى ${priority}`);
    
    return this.updateTicket(id, {
      priority,
      updatedAt: new Date()
    });
  }

  // Ticket responses
  async getTicketResponses(ticketId: string): Promise<TicketResponse[]> {
    logger.info(`جلب ردود التذكرة: ${ticketId}`);
    return this.responses.filter(response => response.ticketId === ticketId);
  }

  async addTicketResponse(ticketId: string, responseData: Partial<TicketResponse>): Promise<TicketResponse> {
    logger.info(`إضافة رد على التذكرة: ${ticketId}`);
    
    const newResponse: TicketResponse = {
      id: (this.responses.length + 1).toString(),
      ticketId,
      message: responseData.message || '',
      isInternal: responseData.isInternal || false,
      authorId: responseData.authorId || '',
      authorType: responseData.authorType || 'support',
      createdAt: new Date(),
      attachments: responseData.attachments || []
    };
    
    this.responses.push(newResponse);
    
    // Update ticket status if it's a customer response
    if (responseData.authorType === 'customer') {
      await this.updateTicketStatus(ticketId, 'open', responseData.authorId || '');
    }
    
    return newResponse;
  }

  // Knowledge base
  async getKnowledgeBase(query: any): Promise<{ articles: KnowledgeBase[]; total: number; page: number; limit: number }> {
    logger.info('جلب قاعدة المعرفة');
    
    let filteredArticles = [...this.knowledgeBase];
    
    if (query.category) {
      filteredArticles = filteredArticles.filter(article => article.category === query.category);
    }
    
    if (query.isPublic !== undefined) {
      filteredArticles = filteredArticles.filter(article => article.isPublic === (query.isPublic === 'true'));
    }
    
    if (query.search) {
      const searchTerm = query.search.toLowerCase();
      filteredArticles = filteredArticles.filter(article => 
        article.title.toLowerCase().includes(searchTerm) ||
        article.content.toLowerCase().includes(searchTerm) ||
        article.tags.some(tag => tag.toLowerCase().includes(searchTerm))
      );
    }
    
    // Pagination
    const page = parseInt(query.page) || 1;
    const limit = parseInt(query.limit) || 10;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    
    const paginatedArticles = filteredArticles.slice(startIndex, endIndex);
    
    return {
      articles: paginatedArticles,
      total: filteredArticles.length,
      page,
      limit
    };
  }

  async createKnowledgeBaseArticle(articleData: Partial<KnowledgeBase>): Promise<KnowledgeBase> {
    logger.info('إنشاء مقال جديد في قاعدة المعرفة');
    
    const newArticle: KnowledgeBase = {
      id: (this.knowledgeBase.length + 1).toString(),
      title: articleData.title || '',
      content: articleData.content || '',
      category: articleData.category || 'general',
      tags: articleData.tags || [],
      isPublic: articleData.isPublic || false,
      views: 0,
      helpful: 0,
      notHelpful: 0,
      createdBy: articleData.createdBy || '',
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    this.knowledgeBase.push(newArticle);
    return newArticle;
  }

  async getKnowledgeBaseArticle(id: string): Promise<KnowledgeBase | null> {
    logger.info(`جلب مقال قاعدة المعرفة: ${id}`);
    const article = this.knowledgeBase.find(article => article.id === id);
    
    if (article) {
      // Increment view count
      article.views++;
    }
    
    return article || null;
  }

  async updateKnowledgeBaseArticle(id: string, updateData: Partial<KnowledgeBase>): Promise<KnowledgeBase | null> {
    logger.info(`تحديث مقال قاعدة المعرفة: ${id}`);
    
    const articleIndex = this.knowledgeBase.findIndex(article => article.id === id);
    if (articleIndex === -1) return null;
    
    this.knowledgeBase[articleIndex] = {
      ...this.knowledgeBase[articleIndex],
      ...updateData,
      updatedAt: new Date()
    };
    
    return this.knowledgeBase[articleIndex];
  }

  async deleteKnowledgeBaseArticle(id: string): Promise<boolean> {
    logger.info(`حذف مقال قاعدة المعرفة: ${id}`);
    
    const articleIndex = this.knowledgeBase.findIndex(article => article.id === id);
    if (articleIndex === -1) return false;
    
    this.knowledgeBase.splice(articleIndex, 1);
    return true;
  }

  async rateKnowledgeBaseArticle(id: string, isHelpful: boolean): Promise<KnowledgeBase | null> {
    logger.info(`تقييم مقال قاعدة المعرفة: ${id}`);
    
    const article = this.knowledgeBase.find(article => article.id === id);
    if (!article) return null;
    
    if (isHelpful) {
      article.helpful++;
    } else {
      article.notHelpful++;
    }
    
    return article;
  }

  // FAQ management
  async getFAQs(query: any): Promise<{ faqs: FAQ[]; total: number; page: number; limit: number }> {
    logger.info('جلب الأسئلة الشائعة');
    
    let filteredFAQs = [...this.faqs];
    
    if (query.category) {
      filteredFAQs = filteredFAQs.filter(faq => faq.category === query.category);
    }
    
    if (query.isPublic !== undefined) {
      filteredFAQs = filteredFAQs.filter(faq => faq.isPublic === (query.isPublic === 'true'));
    }
    
    if (query.search) {
      const searchTerm = query.search.toLowerCase();
      filteredFAQs = filteredFAQs.filter(faq => 
        faq.question.toLowerCase().includes(searchTerm) ||
        faq.answer.toLowerCase().includes(searchTerm) ||
        faq.tags.some(tag => tag.toLowerCase().includes(searchTerm))
      );
    }
    
    // Sort by order
    filteredFAQs.sort((a, b) => a.order - b.order);
    
    // Pagination
    const page = parseInt(query.page) || 1;
    const limit = parseInt(query.limit) || 10;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    
    const paginatedFAQs = filteredFAQs.slice(startIndex, endIndex);
    
    return {
      faqs: paginatedFAQs,
      total: filteredFAQs.length,
      page,
      limit
    };
  }

  async createFAQ(faqData: Partial<FAQ>): Promise<FAQ> {
    logger.info('إنشاء سؤال شائع جديد');
    
    const newFAQ: FAQ = {
      id: (this.faqs.length + 1).toString(),
      question: faqData.question || '',
      answer: faqData.answer || '',
      category: faqData.category || 'general',
      tags: faqData.tags || [],
      isPublic: faqData.isPublic || false,
      views: 0,
      helpful: 0,
      notHelpful: 0,
      order: faqData.order || this.faqs.length + 1,
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    this.faqs.push(newFAQ);
    return newFAQ;
  }

  async getFAQ(id: string): Promise<FAQ | null> {
    logger.info(`جلب السؤال الشائع: ${id}`);
    const faq = this.faqs.find(faq => faq.id === id);
    
    if (faq) {
      // Increment view count
      faq.views++;
    }
    
    return faq || null;
  }

  async updateFAQ(id: string, updateData: Partial<FAQ>): Promise<FAQ | null> {
    logger.info(`تحديث السؤال الشائع: ${id}`);
    
    const faqIndex = this.faqs.findIndex(faq => faq.id === id);
    if (faqIndex === -1) return null;
    
    this.faqs[faqIndex] = {
      ...this.faqs[faqIndex],
      ...updateData,
      updatedAt: new Date()
    };
    
    return this.faqs[faqIndex];
  }

  async deleteFAQ(id: string): Promise<boolean> {
    logger.info(`حذف السؤال الشائع: ${id}`);
    
    const faqIndex = this.faqs.findIndex(faq => faq.id === id);
    if (faqIndex === -1) return false;
    
    this.faqs.splice(faqIndex, 1);
    return true;
  }

  async rateFAQ(id: string, isHelpful: boolean): Promise<FAQ | null> {
    logger.info(`تقييم السؤال الشائع: ${id}`);
    
    const faq = this.faqs.find(faq => faq.id === id);
    if (!faq) return null;
    
    if (isHelpful) {
      faq.helpful++;
    } else {
      faq.notHelpful++;
    }
    
    return faq;
  }

  // Analytics and reporting
  async getTicketStats(period: string = '30d'): Promise<any> {
    logger.info(`جلب إحصائيات التذاكر للفترة: ${period}`);
    
    const stats = {
      totalTickets: this.tickets.length,
      openTickets: this.tickets.filter(t => t.status === 'open').length,
      inProgressTickets: this.tickets.filter(t => t.status === 'in-progress').length,
      resolvedTickets: this.tickets.filter(t => t.status === 'resolved').length,
      closedTickets: this.tickets.filter(t => t.status === 'closed').length,
      escalatedTickets: this.tickets.filter(t => t.status === 'escalated').length,
      priorityDistribution: {
        low: this.tickets.filter(t => t.priority === 'low').length,
        medium: this.tickets.filter(t => t.priority === 'medium').length,
        high: this.tickets.filter(t => t.priority === 'high').length,
        urgent: this.tickets.filter(t => t.priority === 'urgent').length
      },
      categoryDistribution: this.getCategoryDistribution(),
      averageResolutionTime: this.calculateAverageResolutionTime(),
      customerSatisfactionRating: this.calculateCustomerSatisfactionRating()
    };
    
    return stats;
  }

  async getSupportPerformance(period: string = '30d'): Promise<any> {
    logger.info(`جلب أداء الدعم للفترة: ${period}`);
    
    return {
      totalAgents: this.supportTeam.length,
      onlineAgents: this.supportTeam.filter(agent => agent.isOnline).length,
      averageResponseTime: this.calculateAverageResponseTime(),
      ticketsPerAgent: this.calculateTicketsPerAgent(),
      customerSatisfactionByAgent: this.calculateSatisfactionByAgent(),
      resolutionRate: this.calculateResolutionRate()
    };
  }

  async getTicketReports(query: any): Promise<any> {
    logger.info('جلب تقارير التذاكر');
    
    return {
      dailyTicketCreation: this.getDailyTicketCreation(),
      weeklyResolutionStats: this.getWeeklyResolutionStats(),
      monthlyTrends: this.getMonthlyTrends(),
      agentPerformance: this.getAgentPerformanceReport()
    };
  }

  async getCustomerSatisfactionReports(period: string = '30d'): Promise<any> {
    logger.info(`جلب تقارير رضا العملاء للفترة: ${period}`);
    
    const ticketsWithFeedback = this.tickets.filter(t => t.customerFeedback);
    
    return {
      averageRating: this.calculateAverageRating(ticketsWithFeedback),
      ratingDistribution: this.getRatingDistribution(ticketsWithFeedback),
      feedbackComments: ticketsWithFeedback.map(t => ({
        ticketId: t.id,
        rating: t.customerFeedback?.rating,
        comment: t.customerFeedback?.comment,
        submittedAt: t.customerFeedback?.submittedAt
      })),
      improvementAreas: this.identifyImprovementAreas(ticketsWithFeedback)
    };
  }

  // Customer feedback
  async addCustomerFeedback(ticketId: string, feedback: { rating: number; comment: string }): Promise<SupportTicket | null> {
    logger.info(`إضافة تقييم العميل للتذكرة: ${ticketId}`);
    
    const ticket = this.tickets.find(t => t.id === ticketId);
    if (!ticket) return null;
    
    ticket.customerFeedback = {
      rating: feedback.rating,
      comment: feedback.comment,
      submittedAt: new Date()
    };
    
    return ticket;
  }

  async getCustomerFeedback(query: any): Promise<{ feedback: any[]; total: number; page: number; limit: number }> {
    logger.info('جلب تقييمات العملاء');
    
    const ticketsWithFeedback = this.tickets.filter(t => t.customerFeedback);
    
    let filteredFeedback = ticketsWithFeedback.map(t => ({
      ticketId: t.id,
      ticketTitle: t.title,
      customerId: t.customerId,
      assignedTo: t.assignedTo,
      rating: t.customerFeedback?.rating,
      comment: t.customerFeedback?.comment,
      submittedAt: t.customerFeedback?.submittedAt
    }));
    
    if (query.rating) {
      filteredFeedback = filteredFeedback.filter(f => f.rating === parseInt(query.rating));
    }
    
    if (query.assignedTo) {
      filteredFeedback = filteredFeedback.filter(f => f.assignedTo === query.assignedTo);
    }
    
    // Pagination
    const page = parseInt(query.page) || 1;
    const limit = parseInt(query.limit) || 10;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    
    const paginatedFeedback = filteredFeedback.slice(startIndex, endIndex);
    
    return {
      feedback: paginatedFeedback,
      total: filteredFeedback.length,
      page,
      limit
    };
  }

  // Support team management
  async getSupportTeam(): Promise<SupportTeamMember[]> {
    logger.info('جلب فريق الدعم');
    return this.supportTeam;
  }

  async getTeamMemberPerformance(memberId: string, period: string = '30d'): Promise<any> {
    logger.info(`جلب أداء عضو الفريق: ${memberId} للفترة: ${period}`);
    
    const member = this.supportTeam.find(m => m.id === memberId);
    if (!member) return null;
    
    const memberTickets = this.tickets.filter(t => t.assignedTo === memberId);
    
    return {
      member: {
        id: member.id,
        name: member.name,
        email: member.email,
        role: member.role,
        department: member.department
      },
      stats: {
        totalTickets: memberTickets.length,
        resolvedTickets: memberTickets.filter(t => t.status === 'resolved').length,
        averageResolutionTime: this.calculateMemberAverageResolutionTime(memberId),
        customerSatisfactionRating: this.calculateMemberSatisfactionRating(memberId),
        responseTime: this.calculateMemberResponseTime(memberId)
      }
    };
  }

  // Escalation management
  async escalateTicket(ticketId: string, escalationData: { reason: string; escalatedTo: string }): Promise<SupportTicket | null> {
    logger.info(`تصعيد التذكرة: ${ticketId}`);
    
    const ticket = this.tickets.find(t => t.id === ticketId);
    if (!ticket) return null;
    
    ticket.status = 'escalated';
    ticket.escalationHistory = ticket.escalationHistory || [];
    ticket.escalationHistory.push({
      escalatedAt: new Date(),
      escalatedBy: ticket.assignedTo || '',
      escalatedTo: escalationData.escalatedTo,
      reason: escalationData.reason
    });
    
    // Assign to escalated agent
    ticket.assignedTo = escalationData.escalatedTo;
    
    return ticket;
  }

  async getEscalatedTickets(query: any): Promise<{ tickets: SupportTicket[]; total: number; page: number; limit: number }> {
    logger.info('جلب التذاكر المصعدة');
    
    let escalatedTickets = this.tickets.filter(t => t.status === 'escalated');
    
    if (query.assignedTo) {
      escalatedTickets = escalatedTickets.filter(t => t.assignedTo === query.assignedTo);
    }
    
    if (query.priority) {
      escalatedTickets = escalatedTickets.filter(t => t.priority === query.priority);
    }
    
    // Sort by creation date (newest first)
    escalatedTickets.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());
    
    // Pagination
    const page = parseInt(query.page) || 1;
    const limit = parseInt(query.limit) || 10;
    const startIndex = (page - 1) * limit;
    const endIndex = startIndex + limit;
    
    const paginatedTickets = escalatedTickets.slice(startIndex, endIndex);
    
    return {
      tickets: paginatedTickets,
      total: escalatedTickets.length,
      page,
      limit
    };
  }

  // Helper methods for calculations
  private getCategoryDistribution(): { [key: string]: number } {
    const distribution: { [key: string]: number } = {};
    
    this.tickets.forEach(ticket => {
      const category = ticket.category || 'uncategorized';
      distribution[category] = (distribution[category] || 0) + 1;
    });
    
    return distribution;
  }

  private calculateAverageResolutionTime(): number {
    const resolvedTickets = this.tickets.filter(t => t.status === 'resolved' && t.resolvedAt);
    if (resolvedTickets.length === 0) return 0;
    
    const totalTime = resolvedTickets.reduce((acc, ticket) => {
      const resolutionTime = ticket.resolvedAt!.getTime() - ticket.createdAt.getTime();
      return acc + resolutionTime;
    }, 0);
    
    return totalTime / resolvedTickets.length / (1000 * 60 * 60); // Convert to hours
  }

  private calculateCustomerSatisfactionRating(): number {
    const ticketsWithRating = this.tickets.filter(t => t.customerFeedback?.rating);
    if (ticketsWithRating.length === 0) return 0;
    
    const totalRating = ticketsWithRating.reduce((acc, ticket) => {
      return acc + (ticket.customerFeedback?.rating || 0);
    }, 0);
    
    return totalRating / ticketsWithRating.length;
  }

  private calculateAverageResponseTime(): number {
    // Mock calculation - in real implementation, this would calculate time to first response
    return 2.5; // hours
  }

  private calculateTicketsPerAgent(): { [key: string]: number } {
    const ticketsPerAgent: { [key: string]: number } = {};
    
    this.tickets.forEach(ticket => {
      const agent = ticket.assignedTo || 'unassigned';
      ticketsPerAgent[agent] = (ticketsPerAgent[agent] || 0) + 1;
    });
    
    return ticketsPerAgent;
  }

  private calculateSatisfactionByAgent(): { [key: string]: number } {
    const satisfactionByAgent: { [key: string]: { total: number; count: number } } = {};
    
    this.tickets
      .filter(t => t.assignedTo && t.customerFeedback?.rating)
      .forEach(ticket => {
        const agent = ticket.assignedTo!;
        const rating = ticket.customerFeedback!.rating!;
        
        if (!satisfactionByAgent[agent]) {
          satisfactionByAgent[agent] = { total: 0, count: 0 };
        }
        
        satisfactionByAgent[agent].total += rating;
        satisfactionByAgent[agent].count += 1;
      });
    
    const result: { [key: string]: number } = {};
    Object.keys(satisfactionByAgent).forEach(agent => {
      result[agent] = satisfactionByAgent[agent].total / satisfactionByAgent[agent].count;
    });
    
    return result;
  }

  private calculateResolutionRate(): number {
    if (this.tickets.length === 0) return 0;
    
    const resolvedTickets = this.tickets.filter(t => t.status === 'resolved' || t.status === 'closed');
    return (resolvedTickets.length / this.tickets.length) * 100;
  }

  private getDailyTicketCreation(): any[] {
    // Mock data - in real implementation, this would aggregate by date
    return [
      { date: '2024-01-01', count: 5 },
      { date: '2024-01-02', count: 8 },
      { date: '2024-01-03', count: 3 }
    ];
  }

  private getWeeklyResolutionStats(): any[] {
    // Mock data
    return [
      { week: '2024-W01', resolved: 25, total: 30 },
      { week: '2024-W02', resolved: 22, total: 28 }
    ];
  }

  private getMonthlyTrends(): any[] {
    // Mock data
    return [
      { month: '2024-01', tickets: 120, resolved: 110, satisfaction: 4.2 },
      { month: '2024-02', tickets: 135, resolved: 125, satisfaction: 4.4 }
    ];
  }

  private getAgentPerformanceReport(): any[] {
    return this.supportTeam.map(agent => ({
      agentId: agent.id,
      name: agent.name,
      ticketsAssigned: this.tickets.filter(t => t.assignedTo === agent.id).length,
      ticketsResolved: this.tickets.filter(t => t.assignedTo === agent.id && t.status === 'resolved').length,
      averageRating: this.calculateMemberSatisfactionRating(agent.id),
      responseTime: this.calculateMemberResponseTime(agent.id)
    }));
  }

  private calculateAverageRating(tickets: SupportTicket[]): number {
    if (tickets.length === 0) return 0;
    
    const totalRating = tickets.reduce((acc, ticket) => {
      return acc + (ticket.customerFeedback?.rating || 0);
    }, 0);
    
    return totalRating / tickets.length;
  }

  private getRatingDistribution(tickets: SupportTicket[]): { [key: number]: number } {
    const distribution: { [key: number]: number } = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };
    
    tickets.forEach(ticket => {
      const rating = ticket.customerFeedback?.rating;
      if (rating && rating >= 1 && rating <= 5) {
        distribution[rating]++;
      }
    });
    
    return distribution;
  }

  private identifyImprovementAreas(tickets: SupportTicket[]): string[] {
    // Simple analysis based on low ratings and common complaint patterns
    const lowRatingTickets = tickets.filter(t => (t.customerFeedback?.rating || 0) <= 2);
    const improvementAreas: string[] = [];
    
    if (lowRatingTickets.length > tickets.length * 0.2) {
      improvementAreas.push('تحسين وقت الاستجابة');
    }
    
    const categoryIssues = this.getCategoryDistribution();
    const maxIssueCategory = Object.keys(categoryIssues).reduce((a, b) => 
      categoryIssues[a] > categoryIssues[b] ? a : b
    );
    
    if (categoryIssues[maxIssueCategory] > tickets.length * 0.3) {
      improvementAreas.push(`تحسين الدعم في فئة: ${maxIssueCategory}`);
    }
    
    return improvementAreas;
  }

  private calculateMemberAverageResolutionTime(memberId: string): number {
    const memberTickets = this.tickets.filter(t => 
      t.assignedTo === memberId && t.status === 'resolved' && t.resolvedAt
    );
    
    if (memberTickets.length === 0) return 0;
    
    const totalTime = memberTickets.reduce((acc, ticket) => {
      const resolutionTime = ticket.resolvedAt!.getTime() - ticket.createdAt.getTime();
      return acc + resolutionTime;
    }, 0);
    
    return totalTime / memberTickets.length / (1000 * 60 * 60); // Convert to hours
  }

  private calculateMemberSatisfactionRating(memberId: string): number {
    const memberTickets = this.tickets.filter(t => 
      t.assignedTo === memberId && t.customerFeedback?.rating
    );
    
    if (memberTickets.length === 0) return 0;
    
    const totalRating = memberTickets.reduce((acc, ticket) => {
      return acc + (ticket.customerFeedback?.rating || 0);
    }, 0);
    
    return totalRating / memberTickets.length;
  }

  private calculateMemberResponseTime(memberId: string): number {
    // Mock calculation - in real implementation, this would calculate actual response times
    return Math.random() * 5 + 1; // Random value between 1-6 hours
  }
}