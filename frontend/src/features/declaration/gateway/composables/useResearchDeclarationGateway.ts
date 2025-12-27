import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import type {
  DeclarationTypeCard,
  DraftDeclarationItem,
} from "../contracts/ResearchDeclarationGateway.contract";
import {
  declarationTypeCardFromDto,
  draftDeclarationItemFromDto,
} from "../contracts/ResearchDeclarationGateway.contract";
import {
  getDeclarationTypeCardsDTO,
  getDraftDeclarationsDTO,
} from "../services/researchDeclarationGateway.service";

// Icons (UI layer)
import {
  BookOpen,
  FileText,
  FlaskConical,
  Presentation,
} from "lucide-vue-next";

export function useResearchDeclarationGateway() {
  const router = useRouter();

  const typeCards = ref<DeclarationTypeCard[]>([]);
  const drafts = ref<DraftDeclarationItem[]>([]);

  const loading = ref(false);
  const error = ref<string | null>(null);

  const iconMap = computed<Record<string, any>>(() => ({
    article: FileText,
    project: FlaskConical,
    book: BookOpen,
    conference: Presentation,
  }));

  async function load() {
    loading.value = true;
    error.value = null;
    try {
      const [typeDTO, draftDTO] = await Promise.all([
        getDeclarationTypeCardsDTO(),
        getDraftDeclarationsDTO(),
      ]);

      typeCards.value = typeDTO.map((d) => {
        const card = declarationTypeCardFromDto(d);
        return { ...card, icon: iconMap.value[card.typeKey] };
      });

      drafts.value = draftDTO.map(draftDeclarationItemFromDto);
    } catch (e) {
      error.value = e instanceof Error ? e.message : String(e);
    } finally {
      loading.value = false;
    }
  }

  function goTo(to: string) {
    // ✅ đúng ý bạn: sau này thay bằng router.push(to) là xong -> mình dùng luôn
    router.push(to).catch(() => {
      // ignore NavigationDuplicated
    });
  }

  function goToDeclarationType(to: string) {
    goTo(to);
  }

  function continueDraft(to: string) {
    goTo(to);
  }

  onMounted(() => {
    load();
  });

  return {
    typeCards,
    drafts,
    loading,
    error,
    load,
    goToDeclarationType,
    continueDraft,
  };
}
