import { Button } from "@/components/ui/button";

export const CTA = () => {
  return (
    <section className="py-20 hero-gradient">
      <div className="container mx-auto text-center">
        <h2 className="text-4xl font-bold mb-6">
          ابدأ في تطوير متجرك اليوم
        </h2>
        <p className="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
          انضم إلى آلاف المتاجر التي تستخدم إضافتنا لإدارة طلباتها بكفاءة
        </p>
        <Button size="lg" className="bg-primary hover:bg-primary/90 text-lg">
          ابدأ الآن مجاناً
        </Button>
      </div>
    </section>
  );
};