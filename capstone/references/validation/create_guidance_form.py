#!/usr/bin/env python3
"""
Create Guidance Office Verification Questionnaire via Google Forms API.
Run locally: python3 create_guidance_form.py

Prerequisites:
- Google Cloud project with Forms API enabled
- OAuth 2.0 credentials (Desktop app) saved as credentials.json
- pip install google-auth google-auth-oauthlib google-auth-httplib2 google-api-python-client
"""

import json
import os
from pathlib import Path

from google.auth.transport.requests import Request
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from googleapiclient.discovery import build

# Scopes needed for Forms API
SCOPES = [
    "https://www.googleapis.com/auth/forms.body",
    "https://www.googleapis.com/auth/forms.responses.readonly",
]

CREDENTIALS_FILE = "credentials.json"
TOKEN_FILE = "token.json"


def get_credentials():
    """Get valid user credentials from storage or run OAuth flow."""
    creds = None
    if os.path.exists(TOKEN_FILE):
        creds = Credentials.from_authorized_user_file(TOKEN_FILE, SCOPES)
    if not creds or not creds.valid:
        if creds and creds.expired and creds.refresh_token:
            creds.refresh(Request())
        else:
            flow = InstalledAppFlow.from_client_secrets_file(CREDENTIALS_FILE, SCOPES)
            creds = flow.run_local_server(port=0)
        with open(TOKEN_FILE, "w") as token:
            token.write(creds.to_json())
    return creds


def build_form(creds):
    """Build the Google Form structure."""
    service = build("forms", "v1", credentials=creds)

    # Form metadata
    form = {
        "info": {
            "title": "ISPSC Guidance Office — Admission Workflow Validation",
            "documentTitle": "Guidance Office Admission Workflow Validation",
            "description": (
                "This form validates workflow details for a BSIT capstone study on college admission testing "
                "at ISPSC Tagudin Campus. Responses are anonymized. Estimated time: 10–15 minutes.\n\n"
                "Researcher: David (BSIT Capstone) | Adviser: Sir Zeus | A.Y. 2026-2027"
            ),
        },
        "settings": {
            "quizSettings": {"isQuiz": False},
            "presentationSettings": {
                "confirmationMessage": {
                    "text": "Thank you! Your responses will help improve the admission process at ISPSC Tagudin."
                }
            },
        },
    }

    # Create the form
    result = service.forms().create(body=form).execute()
    form_id = result["formId"]
    print(f"✅ Form created: {form_id}")
    print(f"   Edit URL: https://docs.google.com/forms/d/{form_id}/edit")
    print(f"   View URL: https://docs.google.com/forms/d/{form_id}/viewform")

    return service, form_id


def add_questions(service, form_id):
    """Add all questions to the form in batches."""

    # Define all question batches
    requests = []

    # ========== Section 1: Respondent Info ==========
    requests.append({
        "createItem": {
            "item": {
                "title": "Section 1: Respondent Information",
                "description": "Basic info about your role (anonymized in analysis).",
                "pageBreakItem": {},
            },
            "location": {"index": 0},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Your role in the Guidance Office",
                "questionItem": {
                    "question": {
                        "required": True,
                        "choiceQuestion": {
                            "type": "DROP_DOWN",
                            "options": [
                                {"value": "Guidance Counselor"},
                                {"value": "Guidance Office Head"},
                                {"value": "Guidance Staff"},
                                {"value": "Other"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 1},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Years in this role",
                "questionItem": {
                    "question": {
                        "required": True,
                        "textQuestion": {"paragraph": False},
                    }
                },
            },
            "location": {"index": 2},
        }
    })

    # ========== Section 2: Scoring Process ==========
    requests.append({
        "createItem": {
            "item": {
                "title": "Section 2: OMR Scoring & Stencil Process",
                "description": "Current answer sheet scoring workflow.",
                "pageBreakItem": {},
            },
            "location": {"index": 3},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "How many admission cycles have you scored using the stencil method?",
                "questionItem": {
                    "question": {
                        "required": False,
                        "textQuestion": {"paragraph": False},
                    }
                },
            },
            "location": {"index": 4},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Typical scoring duration for one admission cycle (500–1000 sheets)",
                "questionItem": {
                    "question": {
                        "required": True,
                        "choiceQuestion": {
                            "type": "RADIO",
                            "options": [
                                {"value": "1 day"},
                                {"value": "2 days"},
                                {"value": "3 days"},
                                {"value": "4+ days"},
                                {"value": "Other (specify in next question)"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 5},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "If 'Other', specify scoring duration:",
                "questionItem": {
                    "question": {"textQuestion": {"paragraph": False}},
                },
            },
            "location": {"index": 6},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Number of staff involved in scoring during peak",
                "questionItem": {
                    "question": {
                        "required": False,
                        "textQuestion": {"paragraph": False},
                    }
                },
            },
            "location": {"index": 7},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Most common scoring errors encountered",
                "questionItem": {
                    "question": {
                        "required": False,
                        "choiceQuestion": {
                            "type": "CHECKBOX",
                            "options": [
                                {"value": "Answer sheet misalignment"},
                                {"value": "Double marks / multiple selections"},
                                {"value": "Erasures / incomplete erasures"},
                                {"value": "Tally / counting errors"},
                                {"value": "Illegible marks"},
                                {"value": "None observed"},
                                {"value": "Other"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 8},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Do you maintain a scoring error log?",
                "questionItem": {
                    "question": {
                        "required": True,
                        "choiceQuestion": {
                            "type": "RADIO",
                            "options": [{"value": "Yes"}, {"value": "No"}],
                        },
                    }
                },
            },
            "location": {"index": 9},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "If yes, approximate error rate per 100 sheets",
                "questionItem": {
                    "question": {"textQuestion": {"paragraph": False}},
                },
            },
            "location": {"index": 10},
        }
    })

    # ========== Section 3: Consultation Workflow ==========
    requests.append({
        "createItem": {
            "item": {
                "title": "Section 3: Applicant Consultation Workflow",
                "description": "How you guide applicants during consultation sessions.",
                "pageBreakItem": {},
            },
            "location": {"index": 11},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Average consultations per day during peak period",
                "questionItem": {
                    "question": {
                        "required": True,
                        "textQuestion": {"paragraph": False},
                    }
                },
            },
            "location": {"index": 12},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Average session length (minutes)",
                "questionItem": {
                    "question": {
                        "required": True,
                        "textQuestion": {"paragraph": False},
                    }
                },
            },
            "location": {"index": 13},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Top 3 questions applicants ask during consultation",
                "questionItem": {
                    "question": {
                        "required": True,
                        "textQuestion": {"paragraph": True},
                    }
                },
            },
            "location": {"index": 14},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Data sources you reference during consultation",
                "questionItem": {
                    "question": {
                        "required": False,
                        "choiceQuestion": {
                            "type": "CHECKBOX",
                            "options": [
                                {"value": "Exam scores"},
                                {"value": "High school grades (SHS Math, English, Science)"},
                                {"value": "Program quotas / slot availability"},
                                {"value": "Career / program information"},
                                {"value": "Program requirements"},
                                {"value": "Other"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 15},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "How do you currently check program slot availability?",
                "questionItem": {
                    "question": {
                        "required": True,
                        "choiceQuestion": {
                            "type": "RADIO",
                            "options": [
                                {"value": "Printed spreadsheet / master list"},
                                {"value": "Digital file (Excel, Google Sheets)"},
                                {"value": "Verbal confirmation from Registrar"},
                                {"value": "Memory / institutional knowledge"},
                                {"value": "Other"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 16},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Rate the mental effort during peak consultation (1 = Very Low, 5 = Very High)",
                "questionItem": {
                    "question": {
                        "required": True,
                        "choiceQuestion": {
                            "type": "RADIO",
                            "options": [
                                {"value": "1 — Very Low"},
                                {"value": "2 — Low"},
                                {"value": "3 — Moderate"},
                                {"value": "4 — High"},
                                {"value": "5 — Very High"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 17},
        }
    })

    # ========== Section 4: Applicant Travel & Visits ==========
    requests.append({
        "createItem": {
            "item": {
                "title": "Section 4: Applicant Travel & Campus Visits",
                "description": "Observations on where applicants come from and how often they visit.",
                "pageBreakItem": {},
            },
            "location": {"index": 18},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Top 5 municipalities/cities applicants come from",
                "questionItem": {
                    "question": {
                        "required": False,
                        "textQuestion": {"paragraph": True},
                    }
                },
            },
            "location": {"index": 19},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Typical number of campus visits per applicant (for the full admission process)",
                "questionItem": {
                    "question": {
                        "required": True,
                        "choiceQuestion": {
                            "type": "RADIO",
                            "options": [
                                {"value": "1 visit"},
                                {"value": "2 visits"},
                                {"value": "3 visits"},
                                {"value": "4+ visits"},
                                {"value": "Varies significantly"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 20},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Purposes of applicant visits (check all that apply)",
                "questionItem": {
                    "question": {
                        "required": False,
                        "choiceQuestion": {
                            "type": "CHECKBOX",
                            "options": [
                                {"value": "Submit requirements"},
                                {"value": "Check exam schedule"},
                                {"value": "Attend examination"},
                                {"value": "Claim results"},
                                {"value": "Consultation / counseling"},
                                {"value": "Other"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 21},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Have you heard applicants mention travel time of more than 2 hours each way?",
                "questionItem": {
                    "question": {
                        "required": True,
                        "choiceQuestion": {
                            "type": "RADIO",
                            "options": [
                                {"value": "Yes, frequently"},
                                {"value": "Yes, occasionally"},
                                {"value": "No"},
                                {"value": "Unsure"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 22},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "If yes, which areas/municipalities?",
                "questionItem": {
                    "question": {"textQuestion": {"paragraph": True}},
                },
            },
            "location": {"index": 23},
        }
    })

    # ========== Section 5: Equipment & Readiness ==========
    requests.append({
        "createItem": {
            "item": {
                "title": "Section 5: Equipment & Digital Readiness",
                "description": "Current scanning/OMR equipment and answer sheet format.",
                "pageBreakItem": {},
            },
            "location": {"index": 24},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Current scanner/camera for document processing (model, or 'none')",
                "questionItem": {
                    "question": {
                        "required": True,
                        "textQuestion": {"paragraph": False},
                    }
                },
            },
            "location": {"index": 25},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Are answer sheets pre-printed with QR codes for applicant ID?",
                "questionItem": {
                    "question": {
                        "required": True,
                        "choiceQuestion": {
                            "type": "RADIO",
                            "options": [
                                {"value": "Yes, all sheets have QR codes"},
                                {"value": "No QR codes"},
                                {"value": "Partially / some batches only"},
                            ],
                        },
                    }
                },
            },
            "location": {"index": 26},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "If no QR codes, how is applicant ID marked on sheets?",
                "questionItem": {
                    "question": {"textQuestion": {"paragraph": False}},
                },
            },
            "location": {"index": 27},
        }
    })

    # ========== Section 6: Open Feedback ==========
    requests.append({
        "createItem": {
            "item": {
                "title": "Section 6: Open Feedback",
                "description": "Your ideas for improvement (open-ended).",
                "pageBreakItem": {},
            },
            "location": {"index": 28},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "One thing you'd change about the current scoring process",
                "questionItem": {
                    "question": {
                        "required": False,
                        "textQuestion": {"paragraph": True},
                    }
                },
            },
            "location": {"index": 29},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "One thing you'd change about the consultation process",
                "questionItem": {
                    "question": {
                        "required": False,
                        "textQuestion": {"paragraph": True},
                    }
                },
            },
            "location": {"index": 30},
        }
    })

    requests.append({
        "createItem": {
            "item": {
                "title": "Any concerns about digitizing these workflows?",
                "questionItem": {
                    "question": {
                        "required": False,
                        "textQuestion": {"paragraph": True},
                    }
                },
            },
            "location": {"index": 31},
        }
    })

    # Execute all requests in one batch
    print(f"📝 Adding {len(requests)} items to form...")
    batch = {"requests": requests}
    service.forms().batchUpdate(formId=form_id, body=batch).execute()
    print("✅ All questions added!")


def main():
    if not os.path.exists(CREDENTIALS_FILE):
        print(f"❌ {CREDENTIALS_FILE} not found!")
        print("   1. Go to Google Cloud Console > APIs & Services > Credentials")
        print("   2. Create OAuth 2.0 Client ID (Desktop application)")
        print("   3. Download JSON and save as 'credentials.json' in this folder")
        print("   4. Enable 'Google Forms API' in the Console")
        return

    creds = get_credentials()
    service, form_id = build_form(creds)
    add_questions(service, form_id)

    print("\n🎉 Done!")
    print(f"   Edit: https://docs.google.com/forms/d/{form_id}/edit")
    print(f"   Share: https://docs.google.com/forms/d/{form_id}/viewform")


if __name__ == "__main__":
    main()